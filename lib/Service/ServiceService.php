<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Service;

use DateTime;
use OCA\LinkBoard\AppInfo\Application;
use OCA\LinkBoard\Db\Service;
use OCA\LinkBoard\Db\ServiceMapper;
use OCA\LinkBoard\Db\CategoryMapper;
use OCA\LinkBoard\Db\StatusCacheMapper;
use OCA\LinkBoard\Db\StatusHistoryMapper;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\IL10N;

class ServiceService {

    public function __construct(
        private ServiceMapper $serviceMapper,
        private CategoryMapper $categoryMapper,
        private StatusCacheMapper $statusCacheMapper,
        private StatusHistoryMapper $statusHistoryMapper,
        private IL10N $l10n,
        private NotificationService $notificationService,
    ) {
    }

    /** @return Service[] */
    public function findAll(string $userId): array {
        return $this->serviceMapper->findAllByUser($userId);
    }

    /** @return Service[] */
    public function findByCategory(int $categoryId, string $userId): array {
        return $this->serviceMapper->findByCategory($categoryId, $userId);
    }

    /** @throws NotFoundException */
    public function find(int $id, string $userId): Service {
        try {
            return $this->serviceMapper->findById($id, $userId);
        } catch (DoesNotExistException | MultipleObjectsReturnedException $e) {
            throw new NotFoundException('Service not found', 0, $e);
        }
    }

    /**
     * @throws NotFoundException
     * @throws ValidationException
     */
    public function create(
        string $userId,
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
        bool $showScrollbar = false,
    ): Service {
        // Validate category exists and belongs to user
        try {
            $this->categoryMapper->findById($categoryId, $userId);
        } catch (DoesNotExistException) {
            throw new NotFoundException('Category not found');
        }

        // Enforce service limit
        $count = $this->serviceMapper->countByUser($userId);
        if ($count >= Application::MAX_SERVICES_PER_USER) {
            throw new ValidationException(
                $this->l10n->t('Maximum number of services reached (%s)', [Application::MAX_SERVICES_PER_USER])
            );
        }

        $now = (new DateTime())->format('Y-m-d H:i:s');
        $sortOrder = $this->serviceMapper->getMaxSortOrder($categoryId, $userId) + 1;

        $service = new Service();
        $service->setUserId($userId);
        $service->setCategoryId($categoryId);
        $service->setName($name);
        $service->setDescription($description);
        $service->setHref($href);
        $service->setIcon($icon);
        $service->setIconColor($iconColor);
        $service->setTarget($target);
        $service->setSortOrder($sortOrder);
        $service->setPingUrl($pingUrl);
        $service->setPingEnabled($pingEnabled);
        $service->setWidgetType($widgetType);
        $service->setWidgetConfig($widgetConfig ? json_encode($widgetConfig) : null);
        $service->setNotificationOverrides($notificationOverrides ? json_encode($notificationOverrides) : null);
        $service->setShowScrollbar($showScrollbar);
        $service->setCreatedAt($now);
        $service->setUpdatedAt($now);

        return $this->serviceMapper->insert($service);
    }

    /** @throws NotFoundException */
    public function update(
        int $id,
        string $userId,
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
        ?bool $showScrollbar = null,
    ): Service {
        $service = $this->find($id, $userId);

        if ($categoryId !== null) {
            try {
                $this->categoryMapper->findById($categoryId, $userId);
            } catch (DoesNotExistException) {
                throw new NotFoundException('Target category not found');
            }
            $service->setCategoryId($categoryId);
        }
        if ($name !== null) { $service->setName($name); }
        if ($description !== null) { $service->setDescription($description); }
        if ($href !== null) { $service->setHref($href); }
        if ($icon !== null) { $service->setIcon($icon); }
        if ($iconColor !== null) { $service->setIconColor($iconColor); }
        if ($target !== null) { $service->setTarget($target); }
        if ($pingUrl !== null) { $service->setPingUrl($pingUrl); }
        if ($pingEnabled !== null) {
            $service->setPingEnabled($pingEnabled);
            if ($pingEnabled === false) {
                $this->statusCacheMapper->deleteByServiceId($id);
                $this->statusHistoryMapper->deleteByServiceId($id);
            }
        }
        if ($widgetType !== null) {
            if ($widgetType === '') {
                // Empty string = clear widget
                $service->setWidgetType(null);
                $service->setWidgetConfig(null);
            } else {
                $service->setWidgetType($widgetType);
            }
        }
        if ($widgetConfig !== null) { $service->setWidgetConfig(json_encode($widgetConfig)); }
        if ($notificationOverrides !== null) { $service->setNotificationOverrides(json_encode($notificationOverrides)); }
        if ($showScrollbar !== null) { $service->setShowScrollbar($showScrollbar); }

        $service->setUpdatedAt((new DateTime())->format('Y-m-d H:i:s'));

        return $this->serviceMapper->update($service);
    }

    /** @throws NotFoundException */
    public function delete(int $id, string $userId): Service {
        $service = $this->find($id, $userId);
        $this->notificationService->clearOfflineNotifications($userId, $id);
        $this->statusHistoryMapper->deleteByServiceId($id);
        return $this->serviceMapper->delete($service);
    }

    /**
     * Move service to another category
     * @throws NotFoundException
     */
    public function move(int $id, int $newCategoryId, string $userId): Service {
        $service = $this->find($id, $userId);

        try {
            $this->categoryMapper->findById($newCategoryId, $userId);
        } catch (DoesNotExistException) {
            throw new NotFoundException('Target category not found');
        }

        $sortOrder = $this->serviceMapper->getMaxSortOrder($newCategoryId, $userId) + 1;
        $service->setCategoryId($newCategoryId);
        $service->setSortOrder($sortOrder);
        $service->setUpdatedAt((new DateTime())->format('Y-m-d H:i:s'));

        return $this->serviceMapper->update($service);
    }

    /**
     * Reorder services
     * @param array<int, int> $order Map of service_id => new_sort_order
     */
    public function reorder(array $order, string $userId): void {
        foreach ($order as $serviceId => $sortOrder) {
            try {
                $service = $this->serviceMapper->findById((int)$serviceId, $userId);
                $service->setSortOrder((int)$sortOrder);
                $service->setUpdatedAt((new DateTime())->format('Y-m-d H:i:s'));
                $this->serviceMapper->update($service);
            } catch (DoesNotExistException | MultipleObjectsReturnedException) {
                // Skip invalid IDs
            }
        }
    }
}
