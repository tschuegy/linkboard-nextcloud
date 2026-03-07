<?php

declare(strict_types=1);

namespace OCA\LinkBoard\Controller;

use OCA\LinkBoard\AppInfo\Application;
use OCA\LinkBoard\Service\CategoryService;
use OCA\LinkBoard\Service\NotFoundException;
use OCA\LinkBoard\Service\ResourceService;
use OCP\AppFramework\ApiController;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;

class ResourceApiController extends ApiController {

    public function __construct(
        IRequest $request,
        private ResourceService $resourceService,
        private CategoryService $categoryService,
        private ?string $userId,
    ) {
        parent::__construct(Application::APP_ID, $request);
    }

    #[NoAdminRequired]
    public function getData(int $categoryId): DataResponse {
        try {
            $category = $this->categoryService->find($categoryId, $this->userId);
        } catch (NotFoundException $e) {
            return new DataResponse(['error' => $e->getMessage()], Http::STATUS_NOT_FOUND);
        }

        if ($category->getType() !== 'resources') {
            return new DataResponse(['error' => 'Category is not a resources type'], Http::STATUS_BAD_REQUEST);
        }

        $configJson = $category->getConfig();
        $config = $configJson ? json_decode($configJson, true) : [];
        if (!is_array($config)) {
            $config = [];
        }

        $data = $this->resourceService->getResources($config);

        return new DataResponse($data);
    }
}
