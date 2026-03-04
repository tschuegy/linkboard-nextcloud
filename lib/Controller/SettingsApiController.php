<?php

declare(strict_types=1);

namespace OCA\LinkBoard\Controller;

use OCA\LinkBoard\AppInfo\Application;
use OCA\LinkBoard\Service\SettingsService;
use OCP\AppFramework\ApiController;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;

class SettingsApiController extends ApiController {

    public function __construct(
        IRequest $request,
        private SettingsService $settingsService,
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
}
