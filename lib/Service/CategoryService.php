<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Service;

use DateTime;
use OCA\LinkBoard\Db\Category;
use OCA\LinkBoard\Db\CategoryMapper;
use OCA\LinkBoard\Db\ServiceMapper;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;

class CategoryService {

    public function __construct(
        private CategoryMapper $categoryMapper,
        private ServiceMapper $serviceMapper,
    ) {
    }

    /** @return Category[] */
    public function findAll(string $userId): array {
        return $this->categoryMapper->findAllByUser($userId);
    }

    /** @throws NotFoundException */
    public function find(int $id, string $userId): Category {
        try {
            return $this->categoryMapper->findById($id, $userId);
        } catch (DoesNotExistException | MultipleObjectsReturnedException $e) {
            throw new NotFoundException('Category not found', 0, $e);
        }
    }

    public function create(
        string $userId,
        string $name,
        ?string $icon = null,
        ?string $tab = null,
        ?int $columns = null,
        bool $collapsed = false,
    ): Category {
        $now = (new DateTime())->format('Y-m-d H:i:s');
        $slug = $this->generateSlug($name, $userId);
        $sortOrder = $this->categoryMapper->getMaxSortOrder($userId) + 1;

        $category = new Category();
        $category->setUserId($userId);
        $category->setName($name);
        $category->setSlug($slug);
        $category->setIcon($icon);
        $category->setSortOrder($sortOrder);
        $category->setCollapsed($collapsed);
        $category->setTab($tab);
        $category->setColumns($columns);
        $category->setCreatedAt($now);
        $category->setUpdatedAt($now);

        return $this->categoryMapper->insert($category);
    }

    /** @throws NotFoundException */
    public function update(
        int $id,
        string $userId,
        ?string $name = null,
        ?string $icon = null,
        ?string $tab = null,
        ?int $columns = null,
        ?bool $collapsed = null,
    ): Category {
        $category = $this->find($id, $userId);

        if ($name !== null) {
            $category->setName($name);
            $category->setSlug($this->generateSlug($name, $userId, $id));
        }
        if ($icon !== null) {
            $category->setIcon($icon);
        }
        if ($tab !== null) {
            $category->setTab($tab);
        }
        if ($columns !== null) {
            $category->setColumns($columns);
        }
        if ($collapsed !== null) {
            $category->setCollapsed($collapsed);
        }

        $category->setUpdatedAt((new DateTime())->format('Y-m-d H:i:s'));

        return $this->categoryMapper->update($category);
    }

    /** @throws NotFoundException */
    public function delete(int $id, string $userId): Category {
        $category = $this->find($id, $userId);
        $this->serviceMapper->deleteByCategory($id, $userId);
        return $this->categoryMapper->delete($category);
    }

    /**
     * Reorder categories
     * @param array<int, int> $order Map of category_id => new_sort_order
     */
    public function reorder(array $order, string $userId): void {
        foreach ($order as $categoryId => $sortOrder) {
            try {
                $category = $this->categoryMapper->findById((int)$categoryId, $userId);
                $category->setSortOrder((int)$sortOrder);
                $category->setUpdatedAt((new DateTime())->format('Y-m-d H:i:s'));
                $this->categoryMapper->update($category);
            } catch (DoesNotExistException | MultipleObjectsReturnedException) {
                // Skip invalid IDs
            }
        }
    }

    private function generateSlug(string $name, string $userId, ?int $excludeId = null): string {
        $slug = preg_replace('/[^a-z0-9]+/', '-', strtolower(trim($name)));
        $slug = trim($slug, '-');

        if (empty($slug)) {
            $slug = 'category';
        }

        $baseSlug = $slug;
        $counter = 1;
        while (true) {
            try {
                $existing = $this->categoryMapper->findBySlug($slug, $userId);
                if ($excludeId !== null && $existing->getId() === $excludeId) {
                    break;
                }
                $slug = $baseSlug . '-' . $counter++;
            } catch (DoesNotExistException) {
                break;
            }
        }

        return $slug;
    }
}
