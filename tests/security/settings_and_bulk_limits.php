<?php

declare(strict_types=1);

namespace OCP {
    interface IMemcache {
        public function add($key, $value, $ttl = 0);
        public function cad($key, $old);
    }

    interface ICacheFactory {
        public function createLocking(string $prefix = ''): IMemcache;
    }

    interface IDBConnection {
    }
}

namespace OCP\AppFramework\Db {
    class QBMapper {
    }

    class DoesNotExistException extends \Exception {
    }

    class MultipleObjectsReturnedException extends \Exception {
    }
}

namespace {
    use OCA\LinkBoard\Db\SettingMapper;
    use OCA\LinkBoard\Service\BulkOperationGuard;
    use OCA\LinkBoard\Service\BulkOperationInProgressException;
    use OCA\LinkBoard\Service\SettingsService;
    use OCA\LinkBoard\Service\StatusCheckService;
    use OCA\LinkBoard\Service\ValidationException;
    use OCP\ICacheFactory;
    use OCP\IMemcache;

    require dirname(__DIR__, 2) . '/vendor/autoload.php';

    // SPDX-License-Identifier: AGPL-3.0-or-later

    function expectSameValue(mixed $expected, mixed $actual, string $description): void {
        if ($expected !== $actual) {
            throw new \RuntimeException($description);
        }
    }

    function expectValidationFailure(callable $callback, string $description): void {
        try {
            $callback();
        } catch (ValidationException) {
            return;
        }

        throw new \RuntimeException('Expected validation failure: ' . $description);
    }

    foreach (SettingMapper::DEFAULTS as $key => $value) {
        SettingsService::normalizeValue($key, $value);
    }

    expectSameValue('true', SettingsService::normalizeValue('show_search', true), 'Boolean normalization failed');
    expectSameValue('false', SettingsService::normalizeValue('show_search', '0'), 'False normalization failed');
    expectSameValue('0.81', SettingsService::normalizeValue('status_bars_opacity', '0.81'), 'Opacity normalization failed');
    expectSameValue('#aBc123', SettingsService::normalizeValue('manual_color_title', '#aBc123'), 'Color normalization failed');
    expectSameValue('/apps/linkboard/background.png', SettingsService::normalizeValue('background_url', '/apps/linkboard/background.png'), 'Relative URL rejected');
    expectSameValue('https://example.com/background.png', SettingsService::normalizeValue('background_url', 'https://example.com/background.png'), 'HTTPS URL rejected');

    $invalidSettings = [
        ['unknown_key', 'value'],
        ['show_search', 'yes'],
        ['max_columns', '500'],
        ['manual_color_title', 'red; background: url(https://example.com)'],
        ['status_bars_opacity', '1.1'],
        ['background_url', 'javascript:alert(1)'],
        ['background_url', 'https://user:password@example.com/image.png'],
        ['background_url', "/image');background-image:url(https://example.com/evil)"],
        ['title', "LinkBoard\nInjected"],
        ['card_style', []],
    ];
    foreach ($invalidSettings as [$key, $value]) {
        expectValidationFailure(
            fn() => SettingsService::normalizeValue($key, $value),
            $key,
        );
    }

    expectSameValue(25, StatusCheckService::MANUAL_MAX_CHECKS, 'Manual status limit changed unexpectedly');
    expectSameValue(30, StatusCheckService::MANUAL_TIME_BUDGET_SECONDS, 'Manual status time budget changed unexpectedly');

    final class TestMemcache implements IMemcache {
        /** @var array<string, mixed> */
        private array $values = [];

        public function add($key, $value, $ttl = 0): bool {
            if (array_key_exists($key, $this->values)) {
                return false;
            }
            $this->values[$key] = $value;
            return true;
        }

        public function cad($key, $old): bool {
            if (($this->values[$key] ?? null) !== $old) {
                return false;
            }
            unset($this->values[$key]);
            return true;
        }
    }

    final class TestCacheFactory implements ICacheFactory {
        public function __construct(private IMemcache $cache) {
        }

        public function createLocking(string $prefix = ''): IMemcache {
            return $this->cache;
        }
    }

    $guard = new BulkOperationGuard(new TestCacheFactory(new TestMemcache()));
    $result = $guard->run('status-check-all', 'user', 30, function () use ($guard): string {
        try {
            $guard->run('status-check-all', 'user', 30, fn(): string => 'unexpected');
        } catch (BulkOperationInProgressException) {
            return 'locked';
        }
        throw new \RuntimeException('Concurrent operation was not rejected');
    });
    expectSameValue('locked', $result, 'Lock result mismatch');

    $afterRelease = $guard->run('status-check-all', 'user', 30, fn(): string => 'released');
    expectSameValue('released', $afterRelease, 'Lock was not released');

    echo "Settings and bulk-operation security checks passed\n";
}
