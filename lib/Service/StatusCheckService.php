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
    ) {
    }

    /**
     * Check status for a single service
     */
    public function checkService(int $serviceId, string $userId): StatusCache {
        $service = $this->serviceMapper->findById($serviceId, $userId);
        $pingUrl = $service->getPingUrl() ?: $service->getHref();

        if (empty($pingUrl)) {
            return $this->saveStatus($serviceId, 'unknown', null, ['error' => 'No URL configured']);
        }

        $settings = $this->settingsService->getAll($userId);
        $timeoutMs = (int)($settings['status_check_timeout'] ?? 5000);

        return $this->performCheck($serviceId, $pingUrl, $timeoutMs, $service->getIgnoreTls());
    }

    /**
     * Check all services that have ping enabled
     */
    public function checkAllEnabled(?string $onlyUserId = null): int {
        $qb = $this->serviceMapper->getDb()->getQueryBuilder();
        $qb->select('id', 'ping_url', 'href', 'user_id', 'name', 'ignore_tls')
            ->from('linkboard_services')
            ->where($qb->expr()->eq('ping_enabled', $qb->createNamedParameter(true, \OCP\DB\QueryBuilder\IQueryBuilder::PARAM_BOOL)));
        if ($onlyUserId !== null) {
            $qb->andWhere($qb->expr()->eq('user_id', $qb->createNamedParameter($onlyUserId)));
        }

        $result = $qb->executeQuery();
        $checked = 0;
        $userSettings = [];

        while ($row = $result->fetch()) {
            $pingUrl = $row['ping_url'] ?: $row['href'];
            if (!empty($pingUrl)) {
                try {
                    $serviceId = (int)$row['id'];
                    $userId = $row['user_id'];
                    $serviceName = $row['name'];

                    // Get existing cache before check to know previous state
                    $previousCache = $this->statusCacheMapper->findByServiceId($serviceId);
                    $wasNotified = $previousCache ? $previousCache->getNotified() : false;

                    // Load user settings (cached per user)
                    $userSettings[$userId] ??= $this->settingsService->getAll($userId);
                    $timeoutMs = (int)($userSettings[$userId]['status_check_timeout'] ?? 5000);

                    $cache = $this->performCheck($serviceId, $pingUrl, $timeoutMs, (bool)$row['ignore_tls']);
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
                        'exception' => $e,
                    ]);
                }
            }
        }
        $result->closeCursor();

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
        $this->requestGuard->assertAllowed($url);
        $verifyTls = $this->appConfig->getValueBool(Application::APP_ID, 'tls_verification_enabled', true) || !$ignoreTls;
        $ch = curl_init();
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

        $startTime = microtime(true);
        curl_exec($ch);
        $responseMs = $this->measureNetworkRtt($ch, $startTime);

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        $errno = curl_errno($ch);
        curl_close($ch);

        if ($errno !== 0) {
            return $this->saveStatus($serviceId, 'offline', $responseMs, [
                'error' => $error,
                'errno' => $errno,
            ]);
        }

        // HEAD returned 5xx — retry with GET (some servers don't support HEAD)
        if ($httpCode >= 500) {
            $this->logger->debug('LinkBoard: HEAD returned ' . $httpCode . ' for ' . $url . ', retrying with GET');
            $ch = curl_init();
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

            $startTime = microtime(true);
            curl_exec($ch);
            $responseMs = $this->measureNetworkRtt($ch, $startTime);

            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            $errno = curl_errno($ch);
            curl_close($ch);

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
