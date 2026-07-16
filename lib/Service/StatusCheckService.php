<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Service;

use DateTime;
use OCA\LinkBoard\Db\StatusCache;
use OCA\LinkBoard\Db\StatusCacheMapper;
use OCA\LinkBoard\Db\StatusHistory;
use OCA\LinkBoard\Db\StatusHistoryMapper;
use OCA\LinkBoard\Db\ServiceMapper;
use Psr\Log\LoggerInterface;
use OCP\IAppConfig;
use OCA\LinkBoard\AppInfo\Application;

class StatusCheckService {

    public const MANUAL_MAX_CHECKS = 25;
    public const MANUAL_TIME_BUDGET_SECONDS = 30;
    private const SERVICE_LOCK_TTL_SECONDS = 45;

    public function __construct(
        private StatusCacheMapper $statusCacheMapper,
        private StatusHistoryMapper $statusHistoryMapper,
        private ServiceMapper $serviceMapper,
        private LoggerInterface $logger,
        private NotificationService $notificationService,
        private NotificationDispatcherService $notificationDispatcher,
        private SettingsService $settingsService,
        private IAppConfig $appConfig,
        private OutboundRequestGuard $requestGuard,
        private BulkOperationGuard $operationGuard,
    ) {
    }

    /**
     * Check status for a single service
     */
    public function checkService(int $serviceId, string $userId): StatusCache {
        $service = $this->serviceMapper->findById($serviceId, $userId);
        $pingUrl = $service->getPingUrl() ?: $service->getHref();

        $settings = $this->settingsService->getAll($userId);
        $timeoutMs = (int)($settings['status_check_timeout'] ?? 5000);

        return $this->operationGuard->run(
            'status-service',
            (string)$serviceId,
            self::SERVICE_LOCK_TTL_SECONDS,
            function () use ($serviceId, $pingUrl, $timeoutMs, $service): StatusCache {
                if (empty($pingUrl)) {
                    return $this->saveStatus($serviceId, 'unknown', null, ['error' => 'No URL configured']);
                }

                return $this->performCheck($serviceId, $pingUrl, $timeoutMs, $service->getIgnoreTls());
            },
        );
    }

    /**
     * Check all services that have ping enabled
     */
    public function checkAllEnabled(
        ?string $onlyUserId = null,
        ?int $maxChecks = null,
        ?int $timeBudgetSeconds = null,
    ): int {
        $qb = $this->serviceMapper->getDb()->getQueryBuilder();
        $qb->select('id', 'ping_url', 'href', 'user_id', 'name', 'ignore_tls')
            ->from('linkboard_services')
            ->where($qb->expr()->eq('ping_enabled', $qb->createNamedParameter(true, \OCP\DB\QueryBuilder\IQueryBuilder::PARAM_BOOL)));
        if ($onlyUserId !== null) {
            $qb->andWhere($qb->expr()->eq('user_id', $qb->createNamedParameter($onlyUserId)));
        }

        $result = $qb->executeQuery();
        $rows = (function () use ($result): \Generator {
            try {
                while (($row = $result->fetch()) !== false) {
                    yield $row;
                }
            } finally {
                $result->closeCursor();
            }
        })();

        $checked = 0;
        $attempted = 0;
        $userSettings = [];
        $deadline = $timeBudgetSeconds !== null
            ? microtime(true) + max(1, $timeBudgetSeconds)
            : null;

        $previousCaches = [];
        if ($onlyUserId !== null) {
            $rows = iterator_to_array($rows, false);
            $serviceIds = array_map(
                static fn(array $row): int => (int)$row['id'],
                $rows,
            );
            if ($serviceIds !== []) {
                foreach ($this->statusCacheMapper->findByServiceIds($serviceIds) as $cache) {
                    $previousCaches[$cache->getServiceId()] = $cache;
                }
            }

            usort(
                $rows,
                static function (array $left, array $right) use ($previousCaches): int {
                    $leftCheck = ($previousCaches[(int)$left['id']] ?? null)?->getLastCheck() ?? '';
                    $rightCheck = ($previousCaches[(int)$right['id']] ?? null)?->getLastCheck() ?? '';
                    return $leftCheck <=> $rightCheck;
                },
            );
        }

        foreach ($rows as $row) {
            $pingUrl = $row['ping_url'] ?: $row['href'];
            if (!empty($pingUrl)) {
                if ($maxChecks !== null && $attempted >= max(0, $maxChecks)) {
                    break;
                }
                if ($deadline !== null && $attempted > 0 && microtime(true) >= $deadline) {
                    break;
                }
                $attempted++;

                try {
                    $serviceId = (int)$row['id'];
                    $userId = $row['user_id'];
                    $serviceName = $row['name'];

                    // Get existing cache before check to know previous state
                    $previousCache = $onlyUserId !== null
                        ? ($previousCaches[$serviceId] ?? null)
                        : $this->statusCacheMapper->findByServiceId($serviceId);
                    $wasNotified = $previousCache ? $previousCache->getNotified() : false;

                    // Load user settings (cached per user)
                    $userSettings[$userId] ??= $this->settingsService->getAll($userId);
                    $timeoutMs = (int)($userSettings[$userId]['status_check_timeout'] ?? 5000);

                    $cache = $this->operationGuard->run(
                        'status-service',
                        (string)$serviceId,
                        self::SERVICE_LOCK_TTL_SECONDS,
                        fn(): StatusCache => $this->performCheck($serviceId, $pingUrl, $timeoutMs, (bool)$row['ignore_tls']),
                    );
                    $checked++;
                    $threshold = (int)($userSettings[$userId]['notify_failures_threshold'] ?? 3);
                    $notifyRecovery = ($userSettings[$userId]['notify_recovery'] ?? 'true') === 'true';

                    if ($cache->getStatus() === 'offline') {
                        if ($cache->getConsecutiveFailures() >= $threshold && !$cache->getNotified()) {
                            $this->notificationDispatcher->dispatchOffline(
                                $userId, $serviceId, $serviceName, $cache->getConsecutiveFailures()
                            );
                            $cache->setNotified(true);
                            $this->statusCacheMapper->update($cache);
                        }
                    } elseif ($cache->getStatus() === 'online' && $wasNotified) {
                        if ($notifyRecovery) {
                            $this->notificationDispatcher->dispatchRecovery($userId, $serviceId, $serviceName);
                        }
                        $this->notificationService->clearOfflineNotifications($userId, $serviceId);
                    }
                } catch (\Throwable $e) {
                    $this->logger->warning('LinkBoard: Status check failed for service ' . $row['id'], [
                        'exceptionClass' => $e::class,
                        'exceptionCode' => $e->getCode(),
                    ]);
                }
            }
        }

        return $checked;
    }

    /**
     * Get status for multiple service IDs
     * @return array<int, array> Map of serviceId => status data
     */
    public function getStatusMap(array $serviceIds): array {
        $statuses = $this->statusCacheMapper->findByServiceIds($serviceIds);
        $map = [];
        foreach ($statuses as $status) {
            $map[$status->getServiceId()] = $status->jsonSerialize();
        }
        return $map;
    }

    /**
     * Perform HTTP check
     */
    private function performCheck(int $serviceId, string $url, int $timeoutMs = 5000, bool $ignoreTls = false): StatusCache {
        $verifyTls = $this->appConfig->getValueBool(Application::APP_ID, 'tls_verification_enabled', true) || !$ignoreTls;
        $ch = curl_init();
        try {
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT_MS => $timeoutMs,
                CURLOPT_CONNECTTIMEOUT_MS => $timeoutMs,
                CURLOPT_NOBODY => true,        // HEAD request
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
                CURLOPT_MAXFILESIZE => OutboundRequestGuard::MAX_RESPONSE_BYTES,
                CURLOPT_SSL_VERIFYPEER => $verifyTls,
                CURLOPT_SSL_VERIFYHOST => $verifyTls ? 2 : 0,
                CURLOPT_USERAGENT => 'LinkBoard/1.0 StatusCheck',
            ]);
            $target = $this->requestGuard->pinCurl($ch, $url);

            $startTime = microtime(true);
            curl_exec($ch);
            $responseMs = $this->measureNetworkRtt($ch, $startTime);

            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $this->requestGuard->assertCurlConnection($ch, $target['addresses']);
            $error = curl_error($ch);
            $errno = curl_errno($ch);
        } finally {
            curl_close($ch);
        }

        if ($errno !== 0) {
            return $this->saveStatus($serviceId, 'offline', $responseMs, [
                'error' => $error,
                'errno' => $errno,
            ]);
        }

        // HEAD returned 5xx — retry with GET (some servers don't support HEAD)
        if ($httpCode >= 500) {
            $this->logger->debug('LinkBoard: HEAD returned ' . $httpCode . ' for service ' . $serviceId . ', retrying with GET');
            $ch = curl_init();
            try {
                curl_setopt_array($ch, [
                    CURLOPT_URL => $url,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT_MS => $timeoutMs,
                    CURLOPT_CONNECTTIMEOUT_MS => $timeoutMs,
                    CURLOPT_FOLLOWLOCATION => false,
                    CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
                    CURLOPT_MAXFILESIZE => OutboundRequestGuard::MAX_RESPONSE_BYTES,
                    CURLOPT_SSL_VERIFYPEER => $verifyTls,
                    CURLOPT_SSL_VERIFYHOST => $verifyTls ? 2 : 0,
                    CURLOPT_USERAGENT => 'LinkBoard/1.0 StatusCheck',
                ]);
                $target = $this->requestGuard->pinCurl($ch, $url);

                $startTime = microtime(true);
                curl_exec($ch);
                $responseMs = $this->measureNetworkRtt($ch, $startTime);

                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $this->requestGuard->assertCurlConnection($ch, $target['addresses']);
                $error = curl_error($ch);
                $errno = curl_errno($ch);
            } finally {
                curl_close($ch);
            }

            if ($errno !== 0) {
                return $this->saveStatus($serviceId, 'offline', $responseMs, [
                    'error' => $error,
                    'errno' => $errno,
                ]);
            }
        }

        $status = ($httpCode >= 200 && $httpCode < 500) ? 'online' : 'offline';

        return $this->saveStatus($serviceId, $status, $responseMs, [
            'httpCode' => $httpCode,
        ]);
    }

    /**
     * Network RTT in ms — TCP handshake time only, excluding DNS, TLS, and
     * server processing. Approximates ICMP ping RTT. Falls back to wall-clock
     * elapsed when TCP never connected (timeout, refused, unreachable).
     *
     * @param \CurlHandle $ch
     */
    private function measureNetworkRtt($ch, float $startTime): int {
        $connectUs    = (int)curl_getinfo($ch, CURLINFO_CONNECT_TIME_T);
        $namelookupUs = (int)curl_getinfo($ch, CURLINFO_NAMELOOKUP_TIME_T);

        if ($connectUs > 0) {
            return (int)round(max(0, $connectUs - $namelookupUs) / 1000);
        }
        return (int)round((microtime(true) - $startTime) * 1000);
    }

    /**
     * Get status history for a service
     * @return StatusHistory[]
     */
    public function getHistory(int $serviceId, string $period = '24h'): array {
        return $this->statusHistoryMapper->findByServiceId($serviceId, $period);
    }

    /**
     * Get status history for many services in a single query.
     * @param int[] $serviceIds
     * @return array<int, StatusHistory[]> serviceId => entries (oldest→newest)
     */
    public function getHistoryForServices(array $serviceIds, string $period = '24h'): array {
        return $this->statusHistoryMapper->findByServiceIdsForPeriod($serviceIds, $period);
    }

    /**
     * Purge history entries older than 7 days
     */
    public function purgeOldHistory(): int {
        return $this->statusHistoryMapper->deleteOlderThan(7);
    }

    /**
     * Save or update status cache entry
     */
    private function saveStatus(int $serviceId, string $status, ?int $responseMs, ?array $details): StatusCache {
        $now = (new DateTime())->format('Y-m-d H:i:s');
        $existing = $this->statusCacheMapper->findByServiceId($serviceId);

        if ($existing) {
            $existing->setStatus($status);
            $existing->setResponseMs($responseMs);
            $existing->setLastCheck($now);
            $existing->setDetails($details ? json_encode($details) : null);

            if ($status === 'offline') {
                $existing->setConsecutiveFailures($existing->getConsecutiveFailures() + 1);
                $existing->setTotalFailures($existing->getTotalFailures() + 1);
            } else {
                $existing->setConsecutiveFailures(0);
                $existing->setNotified(false);
            }

            $cache = $this->statusCacheMapper->update($existing);
        } else {
            $cache = new StatusCache();
            $cache->setServiceId($serviceId);
            $cache->setStatus($status);
            $cache->setResponseMs($responseMs);
            $cache->setLastCheck($now);
            $cache->setDetails($details ? json_encode($details) : null);
            $cache->setConsecutiveFailures($status === 'offline' ? 1 : 0);
            $cache->setTotalFailures($status === 'offline' ? 1 : 0);
            $cache->setNotified(false);
            $cache = $this->statusCacheMapper->insert($cache);
        }

        // Write history entry
        $history = new StatusHistory();
        $history->setServiceId($serviceId);
        $history->setStatus($status);
        $history->setResponseMs($responseMs);
        $history->setCheckedAt($now);
        $history->setDetails($details ? json_encode($details) : null);
        $this->statusHistoryMapper->insert($history);

        return $cache;
    }
}
