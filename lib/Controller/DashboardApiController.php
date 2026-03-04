<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Controller;

use OCA\LinkBoard\AppInfo\Application;
use OCA\LinkBoard\Service\CategoryService;
use OCA\LinkBoard\Service\ServiceService;
use OCA\LinkBoard\Service\SettingsService;
use OCA\LinkBoard\Service\StatusCheckService;
use OCP\AppFramework\ApiController;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;

class DashboardApiController extends ApiController {

    public function __construct(
        IRequest $request,
        private CategoryService $categoryService,
        private ServiceService $serviceService,
        private SettingsService $settingsService,
        private StatusCheckService $statusCheckService,
        private ?string $userId,
    ) {
        parent::__construct(Application::APP_ID, $request);
    }

    /** Get the entire dashboard: settings, categories with services + status */
    #[NoAdminRequired]
    public function index(): DataResponse {
        $settings = $this->settingsService->getAll($this->userId);
        $categories = $this->categoryService->findAll($this->userId);
        $allServices = $this->serviceService->findAll($this->userId);

        // Get all service IDs for status lookup
        $serviceIds = array_map(fn($s) => $s->getId(), $allServices);
        $statusMap = $this->statusCheckService->getStatusMap($serviceIds);

        // Group services by category
        $servicesByCategory = [];
        foreach ($allServices as $service) {
            $catId = $service->getCategoryId();
            $svcData = $service->jsonSerialize();
            // Attach status data
            $svcData['status'] = $statusMap[$service->getId()] ?? null;
            $servicesByCategory[$catId][] = $svcData;
        }

        // Build the response
        $dashboard = [];
        foreach ($categories as $category) {
            $catData = $category->jsonSerialize();
            $catData['services'] = $servicesByCategory[$category->getId()] ?? [];
            $dashboard[] = $catData;
        }

        return new DataResponse([
            'settings' => $settings,
            'categories' => $dashboard,
        ]);
    }
}
