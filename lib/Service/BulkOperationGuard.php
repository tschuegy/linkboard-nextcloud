<?php

declare(strict_types=1);

namespace OCA\LinkBoard\Service;

use OCP\ICacheFactory;
use OCP\IMemcache;

/**
 * Prevents duplicate expensive operations when a locking cache is configured.
 * The TTL guarantees recovery if a PHP worker terminates before releasing it.
 */
final class BulkOperationGuard {

    private IMemcache $cache;

    public function __construct(ICacheFactory $cacheFactory) {
        $this->cache = $cacheFactory->createLocking('linkboard_operations_');
    }

    /**
     * @template T
     * @param callable(): T $callback
     * @return T
     * @throws BulkOperationInProgressException
     */
    public function run(string $operation, string $scope, int $ttl, callable $callback): mixed {
        $key = hash('sha256', $operation . "\0" . $scope);
        $token = bin2hex(random_bytes(16));

        if (!$this->cache->add($key, $token, max(1, min(3600, $ttl)))) {
            throw new BulkOperationInProgressException('Operation already in progress');
        }

        try {
            return $callback();
        } finally {
            try {
                $this->cache->cad($key, $token);
            } catch (\Throwable) {
                // Do not mask the operation result; the lock expires via TTL.
            }
        }
    }
}
