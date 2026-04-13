<?php

declare(strict_types=1);

namespace OCA\LinkBoard\Controller;

use OCA\LinkBoard\AppInfo\Application;
use OCA\LinkBoard\Service\GlobalBoardService;
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
        private GlobalBoardService $globalBoardService,
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

    #[NoAdminRequired]
    public function index(): DataResponse {
        $services = $this->serviceService->findAll($this->effectiveUserId());
        return new DataResponse($services);
    }

    #[NoAdminRequired]
    public function byCategory(int $categoryId): DataResponse {
        $services = $this->serviceService->findByCategory($categoryId, $this->effectiveUserId());
        return new DataResponse($services);
    }

    #[NoAdminRequired]
    public function show(int $id): DataResponse {
        try {
            $service = $this->serviceService->find($id, $this->effectiveUserId());
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
        if (!$this->canWrite()) {
            return new DataResponse(['error' => 'Read-only access'], Http::STATUS_FORBIDDEN);
        }
        try {
            $service = $this->serviceService->create(
                $this->effectiveUserId(), $categoryId, $name, $description, $href,
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
        if (!$this->canWrite()) {
            return new DataResponse(['error' => 'Read-only access'], Http::STATUS_FORBIDDEN);
        }
        try {
            $service = $this->serviceService->update(
                $id, $this->effectiveUserId(), $categoryId, $name, $description, $href,
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
        if (!$this->canWrite()) {
            return new DataResponse(['error' => 'Read-only access'], Http::STATUS_FORBIDDEN);
        }
        try {
            $this->serviceService->delete($id, $this->effectiveUserId());
            return new DataResponse(null, Http::STATUS_NO_CONTENT);
        } catch (NotFoundException $e) {
            return new DataResponse(['error' => $e->getMessage()], Http::STATUS_NOT_FOUND);
        }
    }

    #[NoAdminRequired]
    public function reorder(): DataResponse {
        if (!$this->canWrite()) {
            return new DataResponse(['error' => 'Read-only access'], Http::STATUS_FORBIDDEN);
        }
        $order = $this->request->getParam('order', '[]');
        if (is_string($order)) {
            $order = json_decode($order, true) ?? [];
        }
        $this->serviceService->reorder($order, $this->effectiveUserId());
        return new DataResponse(['status' => 'ok']);
    }

    #[NoAdminRequired]
    public function move(int $id, int $newCategoryId): DataResponse {
        if (!$this->canWrite()) {
            return new DataResponse(['error' => 'Read-only access'], Http::STATUS_FORBIDDEN);
        }
        try {
            $service = $this->serviceService->move($id, $newCategoryId, $this->effectiveUserId());
            return new DataResponse($service);
        } catch (NotFoundException $e) {
            return new DataResponse(['error' => $e->getMessage()], Http::STATUS_NOT_FOUND);
        }
    }
}
