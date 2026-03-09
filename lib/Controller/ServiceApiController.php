<?php

declare(strict_types=1);

namespace OCA\LinkBoard\Controller;

use OCA\LinkBoard\AppInfo\Application;
use OCA\LinkBoard\Service\NotFoundException;
use OCA\LinkBoard\Service\ServiceService;
use OCA\LinkBoard\Service\ValidationException;
use OCP\AppFramework\ApiController;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;

class ServiceApiController extends ApiController {

    public function __construct(
        IRequest $request,
        private ServiceService $serviceService,
        private ?string $userId,
    ) {
        parent::__construct(Application::APP_ID, $request);
    }

    #[NoAdminRequired]
    public function index(): DataResponse {
        $services = $this->serviceService->findAll($this->userId);
        return new DataResponse($services);
    }

    #[NoAdminRequired]
    public function byCategory(int $categoryId): DataResponse {
        $services = $this->serviceService->findByCategory($categoryId, $this->userId);
        return new DataResponse($services);
    }

    #[NoAdminRequired]
    public function show(int $id): DataResponse {
        try {
            $service = $this->serviceService->find($id, $this->userId);
            return new DataResponse($service);
        } catch (NotFoundException $e) {
            return new DataResponse(['error' => $e->getMessage()], Http::STATUS_NOT_FOUND);
        }
    }

    #[NoAdminRequired]
    public function create(
        int $categoryId,
        string $name,
        ?string $description = null,
        ?string $href = null,
        ?string $icon = null,
        ?string $iconColor = null,
        string $target = '_blank',
        ?string $pingUrl = null,
        bool $pingEnabled = false,
        ?string $widgetType = null,
        ?array $widgetConfig = null,
        ?array $notificationOverrides = null,
    ): DataResponse {
        try {
            $service = $this->serviceService->create(
                $this->userId, $categoryId, $name, $description, $href,
                $icon, $iconColor, $target, $pingUrl, $pingEnabled,
                $widgetType, $widgetConfig, $notificationOverrides,
            );
            return new DataResponse($service, Http::STATUS_CREATED);
        } catch (NotFoundException $e) {
            return new DataResponse(['error' => $e->getMessage()], Http::STATUS_NOT_FOUND);
        } catch (ValidationException $e) {
            return new DataResponse(['error' => $e->getMessage()], Http::STATUS_UNPROCESSABLE_ENTITY);
        }
    }

    #[NoAdminRequired]
    public function update(
        int $id,
        ?int $categoryId = null,
        ?string $name = null,
        ?string $description = null,
        ?string $href = null,
        ?string $icon = null,
        ?string $iconColor = null,
        ?string $target = null,
        ?string $pingUrl = null,
        ?bool $pingEnabled = null,
        ?string $widgetType = null,
        ?array $widgetConfig = null,
        ?array $notificationOverrides = null,
    ): DataResponse {
        try {
            $service = $this->serviceService->update(
                $id, $this->userId, $categoryId, $name, $description, $href,
                $icon, $iconColor, $target, $pingUrl, $pingEnabled,
                $widgetType, $widgetConfig, $notificationOverrides,
            );
            return new DataResponse($service);
        } catch (NotFoundException $e) {
            return new DataResponse(['error' => $e->getMessage()], Http::STATUS_NOT_FOUND);
        }
    }

    #[NoAdminRequired]
    public function destroy(int $id): DataResponse {
        try {
            $this->serviceService->delete($id, $this->userId);
            return new DataResponse(null, Http::STATUS_NO_CONTENT);
        } catch (NotFoundException $e) {
            return new DataResponse(['error' => $e->getMessage()], Http::STATUS_NOT_FOUND);
        }
    }

    #[NoAdminRequired]
    public function reorder(): DataResponse {
        $order = $this->request->getParam('order', '[]');
        if (is_string($order)) {
            $order = json_decode($order, true) ?? [];
        }
        $this->serviceService->reorder($order, $this->userId);
        return new DataResponse(['status' => 'ok']);
    }

    #[NoAdminRequired]
    public function move(int $id, int $newCategoryId): DataResponse {
        try {
            $service = $this->serviceService->move($id, $newCategoryId, $this->userId);
            return new DataResponse($service);
        } catch (NotFoundException $e) {
            return new DataResponse(['error' => $e->getMessage()], Http::STATUS_NOT_FOUND);
        }
    }
}
