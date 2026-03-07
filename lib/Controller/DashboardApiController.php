<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Controller;

use OCA\LinkBoard\AppInfo\Application;
use OCA\LinkBoard\Service\CategoryService;
use OCA\LinkBoard\Service\ServiceService;
use OCA\LinkBoard\Service\SettingsService;
use OCA\LinkBoard\Service\StatusCheckService;
use OCA\LinkBoard\Service\VersionCheckService;
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
        private VersionCheckService $versionCheckService,
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

        // Build flat list with services
        $catMap = [];
        foreach ($categories as $category) {
            $catData = $category->jsonSerialize();
            $catData['services'] = $servicesByCategory[$category->getId()] ?? [];
            $catData['children'] = [];
            $catMap[$category->getId()] = $catData;
        }

        // Build tree: attach children to parents
        $dashboard = [];
        foreach ($catMap as $id => $catData) {
            $parentId = $catData['parentId'];
            if ($parentId !== null && isset($catMap[$parentId])) {
                $catMap[$parentId]['children'][] = &$catMap[$id];
            } else {
                $dashboard[] = &$catMap[$id];
            }
        }
        unset($catData);

        $versionInfo = $this->versionCheckService->getVersionInfo(
            ($settings['check_for_updates'] ?? 'true') === 'true'
        );

        return new DataResponse([
            'settings' => $settings,
            'categories' => array_values($dashboard),
            'version' => $versionInfo['version'],
            'latestVersion' => $versionInfo['latestVersion'],
            'latestVersionUrl' => $versionInfo['latestVersionUrl'],
        ]);
    }
}
