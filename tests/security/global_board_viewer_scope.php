<?php

declare(strict_types=1);

// SPDX-License-Identifier: AGPL-3.0-or-later

// Regression test for issue #20: read-only viewers of the Global Board must see
// the source user's settings, icon domains and uploaded icons, and must not be
// able to write settings.

namespace {
    use OCA\LinkBoard\Controller\IconApiController;
    use OCA\LinkBoard\Controller\SettingsApiController;
    use OCA\LinkBoard\Db\Service;
    use OCA\LinkBoard\Db\ServiceMapper;
    use OCA\LinkBoard\Listener\CSPListener;
    use OCA\LinkBoard\Service\GlobalBoardService;
    use OCA\LinkBoard\Service\SettingsService;
    use OCP\AppFramework\Http;
    use OCP\AppFramework\Http\EmptyContentSecurityPolicy;
    use OCP\Files\IAppData;
    use OCP\Files\NotFoundException;
    use OCP\Files\SimpleFS\ISimpleFile;
    use OCP\Files\SimpleFS\ISimpleFolder;
    use OCP\IL10N;
    use OCP\IUser;
    use OCP\IUserSession;
    use OCP\Security\CSP\AddContentSecurityPolicyEvent;

    require dirname(__DIR__, 2) . '/vendor/autoload.php';

    spl_autoload_register(function (string $class): void {
        if (str_starts_with($class, 'OCP\\')) {
            $file = dirname(__DIR__, 2) . '/vendor/nextcloud/ocp/' . str_replace('\\', '/', $class) . '.php';
            if (is_file($file)) {
                require $file;
            }
        }
    });

    /**
     * Create an object implementing $interface; methods not in $overrides throw.
     * @param array<string, \Closure> $overrides
     */
    function stubOf(string $interface, array $overrides = []): object {
        static $counter = 0;
        $className = 'Stub' . (++$counter);
        $ref = new \ReflectionClass($interface);
        $methods = '';
        foreach ($ref->getMethods() as $method) {
            $params = [];
            foreach ($method->getParameters() as $param) {
                $p = ($param->hasType() ? $param->getType() . ' ' : '') . ($param->isVariadic() ? '...' : '') . '$' . $param->getName();
                if ($param->isOptional() && !$param->isVariadic()) {
                    $p .= ' = null';
                    if ($param->hasType() && !$param->getType()->allowsNull()) {
                        $p = '?' . $p;
                    }
                }
                $params[] = $p;
            }
            $returnType = $method->hasReturnType() ? ': ' . $method->getReturnType() : '';
            $static = $method->isStatic() ? 'static ' : '';
            $body = $returnType === ': void'
                ? '$this->call(__FUNCTION__, func_get_args());'
                : 'return $this->call(__FUNCTION__, func_get_args());';
            $methods .= "public {$static}function {$method->getName()}(" . implode(', ', $params) . "){$returnType} { {$body} }\n";
        }
        eval("class {$className} implements \\{$interface} {
            public array \$overrides = [];
            private function call(string \$name, array \$args): mixed {
                if (!isset(\$this->overrides[\$name])) {
                    throw new \\LogicException('Unexpected call to ' . \$name);
                }
                return (\$this->overrides[\$name])(...\$args);
            }
            {$methods}
        }");
        $obj = new $className();
        $obj->overrides = $overrides;
        return $obj;
    }

    /** Instantiate a class without its constructor and set its (promoted) properties. */
    function build(string $class, array $props): object {
        $ref = new \ReflectionClass($class);
        $obj = $ref->newInstanceWithoutConstructor();
        foreach ($props as $name => $value) {
            $ref->getProperty($name)->setValue($obj, $value);
        }
        return $obj;
    }

    function expectSameValue(mixed $expected, mixed $actual, string $description): void {
        if ($expected !== $actual) {
            throw new \RuntimeException($description . ': expected ' . var_export($expected, true) . ', got ' . var_export($actual, true));
        }
    }

    // Global board: source user "admin", viewer "viewer" (read-only), editor "otheradmin" (admin, not source)
    $globalBoard = new class extends GlobalBoardService {
        public function __construct() {
        }
        public function resolve(string $currentUserId): array {
            return [
                'sourceUserId' => 'admin',
                'canEdit' => $currentUserId !== 'viewer',
                'globalBoardActive' => true,
            ];
        }
    };

    $settingsService = new class extends SettingsService {
        public array $written = [];
        public function __construct() {
        }
        public function getAll(string $userId): array {
            return ['owner' => $userId, 'background_url' => 'https://bg.' . $userId . '.example/x.jpg'];
        }
        public function setMultiple(array $settings, string $userId): void {
            $this->written[] = $userId;
        }
        public function set(string $key, mixed $value, string $userId): string {
            $this->written[] = $userId;
            return (string)$value;
        }
    };

    $serviceMapper = new class extends ServiceMapper {
        public function __construct() {
        }
        public function findAllByUser(string $userId): array {
            $service = new Service();
            $service->setIcon('https://icons.' . $userId . '.example/a.png');
            return [$service];
        }
    };

    $l10n = stubOf(IL10N::class, ['t' => fn (string $text) => $text]);

    // Response::cacheFor() resolves ITimeFactory through \OC::$server
    eval('class OC { public static $server; }');
    $timeFactory = stubOf(\OCP\AppFramework\Utility\ITimeFactory::class, ['getTime' => fn () => 0]);
    \OC::$server = new class($timeFactory) {
        public function __construct(private object $timeFactory) {
        }
        public function get(string $name): object {
            return $this->timeFactory;
        }
    };

    // ── 1. CSP listener uses the source user's icon and background domains ──
    $viewer = stubOf(IUser::class, ['getUID' => fn () => 'viewer']);
    $session = stubOf(IUserSession::class, ['getUser' => fn () => $viewer]);
    $listener = new CSPListener($session, $settingsService, $serviceMapper, $globalBoard);
    $event = new class extends AddContentSecurityPolicyEvent {
        public ?EmptyContentSecurityPolicy $policy = null;
        public function __construct() {
        }
        public function addPolicy(EmptyContentSecurityPolicy $csp): void {
            $this->policy = $csp;
        }
    };
    $listener->handle($event);
    $imageDomains = (new \ReflectionProperty(EmptyContentSecurityPolicy::class, 'allowedImageDomains'))->getValue($event->policy);
    expectSameValue(true, in_array('https://icons.admin.example', $imageDomains, true), 'CSP allows source user icon domain');
    expectSameValue(true, in_array('https://bg.admin.example', $imageDomains, true), 'CSP allows source user background domain');
    expectSameValue(false, in_array('https://icons.viewer.example', $imageDomains, true), 'CSP ignores viewer icon domains');

    // ── 2. Settings API: viewers read source settings and cannot write ──
    $settingsController = fn (string $userId) => build(SettingsApiController::class, [
        'settingsService' => $settingsService,
        'globalBoardService' => $globalBoard,
        'l10n' => $l10n,
        'userId' => $userId,
    ]);
    expectSameValue('admin', $settingsController('viewer')->index()->getData()['owner'], 'Viewer reads source user settings');
    expectSameValue(Http::STATUS_FORBIDDEN, $settingsController('viewer')->updateAll(['title' => 'x'])->getStatus(), 'Viewer cannot update all settings');
    expectSameValue(Http::STATUS_FORBIDDEN, $settingsController('viewer')->updateSingle('title', 'x')->getStatus(), 'Viewer cannot update a single setting');
    expectSameValue([], $settingsService->written, 'No settings written by viewer');
    $settingsController('otheradmin')->updateAll(['title' => 'x']);
    $settingsController('otheradmin')->updateSingle('title', 'x');
    expectSameValue(['admin', 'admin'], $settingsService->written, 'Editing admin writes source user settings');

    // ── 3. Icons: served from the source folder, with fallback to the own folder ──
    $iconFile = stubOf(ISimpleFile::class, [
        'getName' => fn () => 'a.png',
        'getMimeType' => fn () => 'image/png',
        'getSize' => fn () => 3,
        'getMTime' => fn () => 0,
        'getETag' => fn () => 'etag',
    ]);
    $folderWith = fn (array $files) => stubOf(ISimpleFolder::class, [
        'getFile' => function (string $name) use ($files, $iconFile) {
            if (!in_array($name, $files, true)) {
                throw new NotFoundException();
            }
            return $iconFile;
        },
        'newFile' => fn (string $name) => $iconFile,
        'getDirectoryListing' => fn () => [],
    ]);
    $folders = [
        'icons_admin' => $folderWith(['source.png']),
        'icons_otheradmin' => $folderWith(['legacy.png']),
    ];
    $requestedFolders = [];
    $appData = stubOf(IAppData::class, [
        'getFolder' => function (string $name) use (&$folders, &$requestedFolders) {
            $requestedFolders[] = $name;
            if (!isset($folders[$name])) {
                throw new NotFoundException();
            }
            return $folders[$name];
        },
    ]);
    $iconController = fn (string $userId) => build(IconApiController::class, [
        'appData' => $appData,
        'l10n' => $l10n,
        'globalBoardService' => $globalBoard,
        'userId' => $userId,
    ]);
    expectSameValue(Http::STATUS_OK, $iconController('viewer')->serve('source.png')->getStatus(), 'Viewer gets source user icon');
    expectSameValue(Http::STATUS_OK, $iconController('otheradmin')->serve('legacy.png')->getStatus(), 'Admin icon in own folder still served');
    expectSameValue(Http::STATUS_NOT_FOUND, $iconController('viewer')->serve('missing.png')->getStatus(), 'Missing icon returns 404');

    $requestedFolders = [];
    $iconController('otheradmin')->index();
    expectSameValue(['icons_admin'], $requestedFolders, 'Editing admin lists source user icons');
    $requestedFolders = [];
    $iconController('viewer')->index();
    expectSameValue(['icons_viewer'], $requestedFolders, 'Viewer lists only own icons');

    // ── 4. Notification channels: editors manage the source user's, viewers only their own ──
    $channelMapper = new class extends \OCA\LinkBoard\Db\NotificationChannelMapper {
        public array $queried = [];
        public function __construct() {
        }
        public function findAllByUser(string $userId): array {
            $this->queried[] = $userId;
            return [];
        }
        public function findById(int $id, string $userId): ?\OCA\LinkBoard\Db\NotificationChannel {
            $this->queried[] = $userId;
            return null;
        }
    };
    $dispatcher = new class extends \OCA\LinkBoard\Service\NotificationDispatcherService {
        public array $tested = [];
        public function __construct() {
        }
        public function testChannel(int $channelId, string $userId): array {
            $this->tested[] = $userId;
            return ['success' => true];
        }
    };
    $channelController = fn (string $userId) => build(\OCA\LinkBoard\Controller\NotificationChannelApiController::class, [
        'mapper' => $channelMapper,
        'dispatcher' => $dispatcher,
        'globalBoardService' => $globalBoard,
        'userId' => $userId,
    ]);
    $channelController('otheradmin')->index();
    $channelController('otheradmin')->destroy(1);
    $channelController('otheradmin')->test(1);
    $channelController('viewer')->index();
    expectSameValue(['admin', 'admin', 'viewer'], $channelMapper->queried, 'Channel reads are scoped to the board owner');
    expectSameValue(['admin'], $dispatcher->tested, 'Editing admin tests source user channels');

    // ── 5. Import/export: editors work on the Global Board, viewers on their own data ──
    $importExport = new class extends \OCA\LinkBoard\Service\ImportExportService {
        public array $calls = [];
        public function __construct() {
        }
        public function export(string $userId): array {
            $this->calls[] = 'export:' . $userId;
            return [];
        }
        public function import(string $userId, array $data, string $mode = 'replace'): array {
            $this->calls[] = 'import:' . $userId;
            return [];
        }
    };
    $request = stubOf(\OCP\IRequest::class, [
        'getParams' => fn () => [],
        'getParam' => fn (string $key, $default = null) => $key === 'payload' ? '{"categories":[]}' : $default,
    ]);
    $importController = fn (string $userId) => build(\OCA\LinkBoard\Controller\ImportExportController::class, [
        'request' => $request,
        'importExportService' => $importExport,
        'l10n' => $l10n,
        'globalBoardService' => $globalBoard,
        'userId' => $userId,
    ]);
    $importController('otheradmin')->exportJson();
    $importController('otheradmin')->importJson();
    $importController('viewer')->exportJson();
    $importController('viewer')->importJson();
    expectSameValue(['export:admin', 'import:admin', 'export:viewer', 'import:viewer'], $importExport->calls, 'Import/export scoped to the board owner');

    echo "OK\n";
}
