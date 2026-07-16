<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Controller;

use OCA\LinkBoard\AppInfo\Application;
use OCA\LinkBoard\Service\BulkOperationGuard;
use OCA\LinkBoard\Service\BulkOperationInProgressException;
use OCA\LinkBoard\Service\GlobalBoardService;
use OCA\LinkBoard\Service\StatusCheckService;
use OCA\LinkBoard\Service\StatusHistoryAggregator;
use OCA\LinkBoard\Service\ServiceService;
use OCA\LinkBoard\Db\StatusCacheMapper;
use OCA\LinkBoard\Service\NotFoundException;
use OCP\AppFramework\ApiController;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\UserRateLimit;
use OCP\AppFramework\Http\DataResponse;
use OCP\IL10N;
use OCP\IRequest;
use Psr\Log\LoggerInterface;

class StatusApiController extends ApiController {

    public function __construct(
        IRequest $request,
        private StatusCheckService $statusCheckService,
        private StatusHistoryAggregator $aggregator,
        private ServiceService $serviceService,
        private StatusCacheMapper $statusCacheMapper,
        private GlobalBoardService $globalBoardService,
        private BulkOperationGuard $operationGuard,
        private LoggerInterface $logger,
        private IL10N $l10n,
        private ?string $userId,
    ) {
        parent::__construct(Application::APP_ID, $request);
    }

    private function effectiveUserId(): string {
        return $this->globalBoardService->resolve($this->userId)['sourceUserId'];
    }

    private function canWrite(): bool {
        return $this->globalBoardService->resolve($this->userId)['canEdit'];
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
    #[UserRateLimit(limit: 20, period: 60)]
    public function check(int $id): DataResponse {
        if (!$this->canWrite()) {
            return new DataResponse([], Http::STATUS_FORBIDDEN);
        }

        try {
            $status = $this->statusCheckService->checkService($id, $this->effectiveUserId());
            return new DataResponse($status);
        } catch (NotFoundException $e) {
            return new DataResponse(['error' => $e->getMessage()], Http::STATUS_NOT_FOUND);
        } catch (BulkOperationInProgressException) {
            return new DataResponse([], Http::STATUS_TOO_MANY_REQUESTS);
        } catch (\Throwable $e) {
            $this->logger->warning('LinkBoard: Manual status check failed', [
                'exceptionClass' => $e::class,
                'exceptionCode' => $e->getCode(),
            ]);
            return new DataResponse(
                ['error' => $this->l10n->t('Status check failed')],
                Http::STATUS_INTERNAL_SERVER_ERROR,
            );
        }
    }

    /**
     * Get status history for a service
     */
    #[NoAdminRequired]
    public function history(int $id, string $period = '24h'): DataResponse {
        if (!in_array($period, ['1h', '3h', '24h', '7d'], true)) {
            $period = '24h';
        }

        try {
            $this->serviceService->find($id, $this->effectiveUserId());

            $history = $this->statusCheckService->getHistory($id, $period);
            $cache = $this->statusCacheMapper->findByServiceId($id);
            $agg = $this->aggregator->aggregate($history, $period);

            $uptimePercent = $agg['total'] > 0
                ? round(($agg['onlineCount'] / $agg['total']) * 100, 2)
                : null;

            return new DataResponse([
                'history' => $agg['history'],
                'totalFailures' => $cache ? $cache->getTotalFailures() : 0,
                'currentStatus' => $cache ? $cache->getStatus() : 'unknown',
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
        $pingServices = array_values(array_filter($services, fn($s) => $s->getPingEnabled()));
        if (empty($pingServices)) {
            return new DataResponse([]);
        }

        $serviceIds = array_map(fn($s) => $s->getId(), $pingServices);

        // 2 queries instead of 2N
        $historyByService = $this->statusCheckService->getHistoryForServices($serviceIds, $period);
        $cacheById = [];
        foreach ($this->statusCacheMapper->findByServiceIds($serviceIds) as $c) {
            $cacheById[$c->getServiceId()] = $c;
        }

        $result = [];
        foreach ($pingServices as $svc) {
            $id = $svc->getId();
            $entries = $historyByService[$id] ?? [];
            $agg = $this->aggregator->aggregate($entries, $period);

            $cache = $cacheById[$id] ?? null;
            $uptimePercent = $agg['total'] > 0
                ? round(($agg['onlineCount'] / $agg['total']) * 100, 2)
                : null;

            $result[$id] = [
                'history' => $agg['history'],
                'totalFailures' => $cache ? $cache->getTotalFailures() : 0,
                'currentStatus' => $cache ? $cache->getStatus() : 'unknown',
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
    #[UserRateLimit(limit: 2, period: 60)]
    public function checkAll(): DataResponse {
        if (!$this->canWrite()) {
            return new DataResponse([], Http::STATUS_FORBIDDEN);
        }

        $effectiveUserId = $this->effectiveUserId();
        try {
            $checked = $this->operationGuard->run(
                'status-check-all',
                $effectiveUserId,
                90,
                fn(): int => $this->statusCheckService->checkAllEnabled(
                    $effectiveUserId,
                    StatusCheckService::MANUAL_MAX_CHECKS,
                    StatusCheckService::MANUAL_TIME_BUDGET_SECONDS,
                ),
            );
        } catch (BulkOperationInProgressException) {
            return new DataResponse([], Http::STATUS_TOO_MANY_REQUESTS);
        } catch (\Throwable $e) {
            $this->logger->warning('LinkBoard: Manual bulk status check failed', [
                'exceptionClass' => $e::class,
                'exceptionCode' => $e->getCode(),
            ]);
            return new DataResponse(
                ['error' => $this->l10n->t('Status check failed')],
                Http::STATUS_INTERNAL_SERVER_ERROR,
            );
        }

        // Return updated status map
        $services = $this->serviceService->findAll($effectiveUserId);
        $serviceIds = array_map(fn($s) => $s->getId(), $services);
        $statusMap = $this->statusCheckService->getStatusMap($serviceIds);

        return new DataResponse([
            'checked' => $checked,
            'statuses' => $statusMap,
        ]);
    }
}
