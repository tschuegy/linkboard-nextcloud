<?php

declare(strict_types=1);

namespace OCA\LinkBoard\Controller;

use OCA\LinkBoard\AppInfo\Application;
use OCA\LinkBoard\Service\CategoryService;
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
        private ?string $userId,
    ) {
        parent::__construct(Application::APP_ID, $request);
    }

    #[NoAdminRequired]
    public function index(): DataResponse {
        $categories = $this->categoryService->findAll($this->userId);
        return new DataResponse($categories);
    }

    #[NoAdminRequired]
    public function show(int $id): DataResponse {
        try {
            $category = $this->categoryService->find($id, $this->userId);
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
    ): DataResponse {
        $category = $this->categoryService->create(
            $this->userId, $name, $icon, $tab, $columns, $collapsed
        );
        return new DataResponse($category, Http::STATUS_CREATED);
    }

    #[NoAdminRequired]
    public function update(
        int $id,
        ?string $name = null,
        ?string $icon = null,
        ?string $tab = null,
        ?int $columns = null,
        ?bool $collapsed = null,
    ): DataResponse {
        try {
            $category = $this->categoryService->update(
                $id, $this->userId, $name, $icon, $tab, $columns, $collapsed
            );
            return new DataResponse($category);
        } catch (NotFoundException $e) {
            return new DataResponse(['error' => $e->getMessage()], Http::STATUS_NOT_FOUND);
        }
    }

    #[NoAdminRequired]
    public function destroy(int $id): DataResponse {
        try {
            $this->categoryService->delete($id, $this->userId);
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
        if (!is_array($order)) {
            $order = [];
        }
        $this->categoryService->reorder($order, $this->userId);
        return new DataResponse(['status' => 'ok']);
    }
}
