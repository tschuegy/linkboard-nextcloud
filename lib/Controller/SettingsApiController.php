<?php

declare(strict_types=1);

namespace OCA\LinkBoard\Controller;

use OCA\LinkBoard\AppInfo\Application;
use OCA\LinkBoard\Service\SettingsService;
use OCP\AppFramework\ApiController;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\IAppConfig;
use OCP\IGroupManager;
use OCP\IRequest;

class SettingsApiController extends ApiController {

    public function __construct(
        IRequest $request,
        private SettingsService $settingsService,
        private IAppConfig $appConfig,
        private IGroupManager $groupManager,
        private ?string $userId,
    ) {
        parent::__construct(Application::APP_ID, $request);
    }

    #[NoAdminRequired]
    public function index(): DataResponse {
        $settings = $this->settingsService->getAll($this->userId);
        return new DataResponse($settings);
    }

    #[NoAdminRequired]
    public function updateAll(array $settings): DataResponse {
        $this->settingsService->setMultiple($settings, $this->userId);
        $allSettings = $this->settingsService->getAll($this->userId);
        return new DataResponse($allSettings);
    }

    #[NoAdminRequired]
    public function updateSingle(string $key, string $value): DataResponse {
        $this->settingsService->set($key, $value, $this->userId);
        return new DataResponse(['key' => $key, 'value' => $value]);
    }

    /** Any user can read admin settings (needed for display) */
    #[NoAdminRequired]
    public function getAdminSettings(): DataResponse {
        return new DataResponse([
            'status_check_interval' => $this->appConfig->getValueInt(Application::APP_ID, 'status_check_interval', 300),
        ]);
    }

    /** Only admins can update admin settings (no #[NoAdminRequired]) */
    public function updateAdminSettings(int $statusCheckInterval): DataResponse {
        $statusCheckInterval = max(60, min(1800, $statusCheckInterval));
        $this->appConfig->setValueInt(Application::APP_ID, 'status_check_interval', $statusCheckInterval);
        return new DataResponse([
            'status_check_interval' => $statusCheckInterval,
        ]);
    }
}
