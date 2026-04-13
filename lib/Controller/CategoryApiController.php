<?php

declare(strict_types=1);

namespace OCA\LinkBoard\Controller;

use OCA\LinkBoard\AppInfo\Application;
use OCA\LinkBoard\Service\CategoryService;
use OCA\LinkBoard\Service\GlobalBoardService;
use OCA\LinkBoard\Service\NotFoundException;
use OCP\AppFramework\ApiController;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;

class CategoryApiController extends ApiController {

    public function __construct(
        IRequest $request,
        private CategoryService $categoryService,
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
        $categories = $this->categoryService->findAll($this->effectiveUserId());
        return new DataResponse($categories);
    }

    #[NoAdminRequired]
    public function show(int $id): DataResponse {
        try {
            $category = $this->categoryService->find($id, $this->effectiveUserId());
            return new DataResponse($category);
        } catch (NotFoundException $e) {
            return new DataResponse(['error' => $e->getMessage()], Http::STATUS_NOT_FOUND);
        }
    }

    #[NoAdminRequired]
    public function create(
        string $name,
        ?string $icon = null,
        ?string $tab = null,
        ?int $columns = null,
        bool $collapsed = false,
        ?int $parentId = null,
        ?string $type = null,
        ?string $config = null,
    ): DataResponse {
        if (!$this->canWrite()) {
            return new DataResponse(['error' => 'Read-only access'], Http::STATUS_FORBIDDEN);
        }
        try {
            $category = $this->categoryService->create(
                $this->effectiveUserId(), $name, $icon, $tab, $columns, $collapsed, $parentId, $type ?? 'default', $config
            );
            return new DataResponse($category, Http::STATUS_CREATED);
        } catch (\InvalidArgumentException $e) {
            return new DataResponse(['error' => $e->getMessage()], Http::STATUS_BAD_REQUEST);
        }
    }

    #[NoAdminRequired]
    public function update(
        int $id,
        ?string $name = null,
        ?string $icon = null,
        ?string $tab = null,
        ?int $columns = null,
        ?bool $collapsed = null,
        ?int $parentId = null,
        ?string $type = null,
        ?string $config = null,
    ): DataResponse {
        if (!$this->canWrite()) {
            return new DataResponse(['error' => 'Read-only access'], Http::STATUS_FORBIDDEN);
        }
        try {
            $updateParent = $this->request->getParam('parentId') !== null;
            $category = $this->categoryService->update(
                $id, $this->effectiveUserId(), $name, $icon, $tab, $columns, $collapsed, $updateParent, $parentId, $type, $config
            );
            return new DataResponse($category);
        } catch (NotFoundException $e) {
            return new DataResponse(['error' => $e->getMessage()], Http::STATUS_NOT_FOUND);
        } catch (\InvalidArgumentException $e) {
            return new DataResponse(['error' => $e->getMessage()], Http::STATUS_BAD_REQUEST);
        }
    }

    #[NoAdminRequired]
    public function destroy(int $id): DataResponse {
        if (!$this->canWrite()) {
            return new DataResponse(['error' => 'Read-only access'], Http::STATUS_FORBIDDEN);
        }
        try {
            $this->categoryService->delete($id, $this->effectiveUserId());
            return new DataResponse(null, Http::STATUS_NO_CONTENT);
        } catch (NotFoundException $e) {
            return new DataResponse(['error' => $e->getMessage()], Http::STATUS_NOT_FOUND);
        }
    }

    #[NoAdminRequired]
    public function moveCategory(int $id): DataResponse {
        if (!$this->canWrite()) {
            return new DataResponse(['error' => 'Read-only access'], Http::STATUS_FORBIDDEN);
        }
        $parentId = $this->request->getParam('parentId');
        $parentId = $parentId !== null && $parentId !== '' ? (int)$parentId : null;
        try {
            $category = $this->categoryService->moveCategory($id, $parentId, $this->effectiveUserId());
            return new DataResponse($category);
        } catch (NotFoundException $e) {
            return new DataResponse(['error' => $e->getMessage()], Http::STATUS_NOT_FOUND);
        } catch (\InvalidArgumentException $e) {
            return new DataResponse(['error' => $e->getMessage()], Http::STATUS_BAD_REQUEST);
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
        if (!is_array($order)) {
            $order = [];
        }
        $this->categoryService->reorder($order, $this->effectiveUserId());
        return new DataResponse(['status' => 'ok']);
    }
}
