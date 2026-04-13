<?php
declare(strict_types=1);
namespace OCA\LinkBoard\AppInfo;

use OCA\LinkBoard\Listener\CSPListener;
use OCA\LinkBoard\Middleware\GroupRestrictionMiddleware;
use OCA\LinkBoard\Service\GroupAccessService;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\INavigationManager;
use OCP\IURLGenerator;
use OCP\IUserSession;
use OCP\Security\CSP\AddContentSecurityPolicyEvent;

class Application extends App implements IBootstrap {

    public const APP_ID = 'linkboard';
    public const MAX_SERVICES_PER_USER = 500;

    public function __construct(array $urlParams = []) {
        parent::__construct(self::APP_ID, $urlParams);
    }

    public function register(IRegistrationContext $context): void {
        $context->registerEventListener(AddContentSecurityPolicyEvent::class, CSPListener::class);
        $context->registerNotifierService(\OCA\LinkBoard\Notification\Notifier::class);
        $context->registerMiddleware(GroupRestrictionMiddleware::class);
    }

    public function boot(IBootContext $context): void {
        $server = $context->getServerContainer();
        $server->get(INavigationManager::class)->add(function () use ($server) {
            $userSession = $server->get(IUserSession::class);
            $user = $userSession->getUser();

            $urlGenerator = $server->get(IURLGenerator::class);
            $entry = [
                'id' => self::APP_ID,
                'order' => 10,
                'href' => $urlGenerator->linkToRoute('linkboard.page.index'),
                'icon' => $urlGenerator->imagePath('linkboard', 'app.svg'),
                'name' => 'LinkBoard',
                'app' => self::APP_ID,
            ];

            if ($user !== null) {
                $accessService = $server->get(GroupAccessService::class);
                if (!$accessService->isUserAllowed($user->getUID())) {
                    $entry['type'] = 'hidden';
                }
            }

            return $entry;
        });
    }
}
