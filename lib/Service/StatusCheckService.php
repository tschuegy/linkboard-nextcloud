<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Service;

use DateTime;
use OCA\LinkBoard\Db\StatusCache;
use OCA\LinkBoard\Db\StatusCacheMapper;
use OCA\LinkBoard\Db\ServiceMapper;
use Psr\Log\LoggerInterface;

class StatusCheckService {

    public function __construct(
        private StatusCacheMapper $statusCacheMapper,
        private ServiceMapper $serviceMapper,
        private LoggerInterface $logger,
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

        return $this->performCheck($serviceId, $pingUrl);
    }

    /**
     * Check all services that have ping enabled
     */
    public function checkAllEnabled(): int {
        // Find all services with ping_enabled = true
        $qb = $this->serviceMapper->getDb()->getQueryBuilder();
        $qb->select('id', 'ping_url', 'href')
            ->from('linkboard_services')
            ->where($qb->expr()->eq('ping_enabled', $qb->createNamedParameter(true, \OCP\DB\QueryBuilder\IQueryBuilder::PARAM_BOOL)));

        $result = $qb->executeQuery();
        $checked = 0;

        while ($row = $result->fetch()) {
            $pingUrl = $row['ping_url'] ?: $row['href'];
            if (!empty($pingUrl)) {
                try {
                    $this->performCheck((int)$row['id'], $pingUrl);
                    $checked++;
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
    private function performCheck(int $serviceId, string $url): StatusCache {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_NOBODY => true,        // HEAD request
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_SSL_VERIFYPEER => false, // Allow self-signed certs (homelab)
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_USERAGENT => 'LinkBoard/1.0 StatusCheck',
        ]);

        $startTime = microtime(true);
        curl_exec($ch);
        $responseMs = (int)round((microtime(true) - $startTime) * 1000);

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

        $status = ($httpCode >= 200 && $httpCode < 500) ? 'online' : 'offline';

        return $this->saveStatus($serviceId, $status, $responseMs, [
            'httpCode' => $httpCode,
        ]);
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
            return $this->statusCacheMapper->update($existing);
        }

        $cache = new StatusCache();
        $cache->setServiceId($serviceId);
        $cache->setStatus($status);
        $cache->setResponseMs($responseMs);
        $cache->setLastCheck($now);
        $cache->setDetails($details ? json_encode($details) : null);
        return $this->statusCacheMapper->insert($cache);
    }
}
