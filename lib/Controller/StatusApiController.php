<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Controller;

use OCA\LinkBoard\AppInfo\Application;
use OCA\LinkBoard\Service\StatusCheckService;
use OCA\LinkBoard\Service\ServiceService;
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
        private ?string $userId,
    ) {
        parent::__construct(Application::APP_ID, $request);
    }

    /**
     * Get status for all user's services that have ping enabled
     */
    #[NoAdminRequired]
    public function index(): DataResponse {
        $services = $this->serviceService->findAll($this->userId);
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
            $status = $this->statusCheckService->checkService($id, $this->userId);
            return new DataResponse($status);
        } catch (NotFoundException $e) {
            return new DataResponse(['error' => $e->getMessage()], Http::STATUS_NOT_FOUND);
        } catch (\Throwable $e) {
            return new DataResponse(['error' => 'Status check failed: ' . $e->getMessage()], Http::STATUS_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Trigger status check for all enabled services
     */
    #[NoAdminRequired]
    public function checkAll(): DataResponse {
        $checked = $this->statusCheckService->checkAllEnabled();

        // Return updated status map
        $services = $this->serviceService->findAll($this->userId);
        $serviceIds = array_map(fn($s) => $s->getId(), $services);
        $statusMap = $this->statusCheckService->getStatusMap($serviceIds);

        return new DataResponse([
            'checked' => $checked,
            'statuses' => $statusMap,
        ]);
    }
}
