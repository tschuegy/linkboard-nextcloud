<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Controller;

use OCA\LinkBoard\AppInfo\Application;
use OCA\LinkBoard\Service\GlobalBoardService;
use OCA\LinkBoard\Service\StatusCheckService;
use OCA\LinkBoard\Service\ServiceService;
use OCA\LinkBoard\Db\StatusCacheMapper;
use OCA\LinkBoard\Service\NotFoundException;
use OCP\AppFramework\ApiController;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;

class StatusApiController extends ApiController {

    public function __construct(
        IRequest $request,
        private StatusCheckService $statusCheckService,
        private ServiceService $serviceService,
        private StatusCacheMapper $statusCacheMapper,
        private GlobalBoardService $globalBoardService,
        private ?string $userId,
    ) {
        parent::__construct(Application::APP_ID, $request);
    }

    private function effectiveUserId(): string {
        return $this->globalBoardService->resolve($this->userId)['sourceUserId'];
    }

    /**
     * Get status for all user's services that have ping enabled
     */
    #[NoAdminRequired]
    public function index(): DataResponse {
        $services = $this->serviceService->findAll($this->effectiveUserId());
        $serviceIds = array_map(fn($s) => $s->getId(), $services);
        $statusMap = $this->statusCheckService->getStatusMap($serviceIds);

        return new DataResponse($statusMap);
    }

    /**
     * Trigger a status check for a specific service
     */
    #[NoAdminRequired]
    public function check(int $id): DataResponse {
        try {
            $status = $this->statusCheckService->checkService($id, $this->effectiveUserId());
            return new DataResponse($status);
        } catch (NotFoundException $e) {
            return new DataResponse(['error' => $e->getMessage()], Http::STATUS_NOT_FOUND);
        } catch (\Throwable $e) {
            return new DataResponse(['error' => 'Status check failed: ' . $e->getMessage()], Http::STATUS_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get status history for a service
     */
    #[NoAdminRequired]
    public function history(int $id, string $period = '24h'): DataResponse {
        // Validate period
        if (!in_array($period, ['1h', '3h', '24h', '7d'], true)) {
            $period = '24h';
        }

        try {
            // Verify ownership
            $this->serviceService->find($id, $this->effectiveUserId());

            $history = $this->statusCheckService->getHistory($id, $period);
            $cache = $this->statusCacheMapper->findByServiceId($id);

            $totalFailures = $cache ? $cache->getTotalFailures() : 0;
            $currentStatus = $cache ? $cache->getStatus() : 'unknown';

            // Calculate uptime percentage
            $total = count($history);
            $online = 0;
            foreach ($history as $entry) {
                if ($entry->getStatus() === 'online') {
                    $online++;
                }
            }
            $uptimePercent = $total > 0 ? round(($online / $total) * 100, 2) : null;

            return new DataResponse([
                'history' => array_map(fn($h) => $h->jsonSerialize(), $history),
                'totalFailures' => $totalFailures,
                'currentStatus' => $currentStatus,
                'uptimePercent' => $uptimePercent,
                'period' => $period,
            ]);
        } catch (NotFoundException $e) {
            return new DataResponse(['error' => $e->getMessage()], Http::STATUS_NOT_FOUND);
        }
    }

    /**
     * Get status history for all ping-enabled services
     */
    #[NoAdminRequired]
    public function historyAll(string $period = '24h'): DataResponse {
        if (!in_array($period, ['1h', '3h', '24h', '7d'], true)) {
            $period = '24h';
        }

        $services = $this->serviceService->findAll($this->effectiveUserId());
        $result = [];

        foreach ($services as $service) {
            if (!$service->getPingEnabled()) {
                continue;
            }
            $id = $service->getId();
            $history = $this->statusCheckService->getHistory($id, $period);
            $cache = $this->statusCacheMapper->findByServiceId($id);

            $totalFailures = $cache ? $cache->getTotalFailures() : 0;
            $currentStatus = $cache ? $cache->getStatus() : 'unknown';

            $total = count($history);
            $online = 0;
            foreach ($history as $entry) {
                if ($entry->getStatus() === 'online') {
                    $online++;
                }
            }
            $uptimePercent = $total > 0 ? round(($online / $total) * 100, 2) : null;

            $result[$id] = [
                'history' => array_map(fn($h) => $h->jsonSerialize(), $history),
                'totalFailures' => $totalFailures,
                'currentStatus' => $currentStatus,
                'uptimePercent' => $uptimePercent,
                'period' => $period,
            ];
        }

        return new DataResponse($result);
    }

    /**
     * Trigger status check for all enabled services
     */
    #[NoAdminRequired]
    public function checkAll(): DataResponse {
        $checked = $this->statusCheckService->checkAllEnabled();

        // Return updated status map
        $services = $this->serviceService->findAll($this->effectiveUserId());
        $serviceIds = array_map(fn($s) => $s->getId(), $services);
        $statusMap = $this->statusCheckService->getStatusMap($serviceIds);

        return new DataResponse([
            'checked' => $checked,
            'statuses' => $statusMap,
        ]);
    }
}
