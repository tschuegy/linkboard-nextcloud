<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Db;

use JsonSerializable;
use OCP\AppFramework\Db\Entity;

/**
 * @method int getCategoryId()
 * @method void setCategoryId(int $categoryId)
 * @method string getUserId()
 * @method void setUserId(string $userId)
 * @method string getName()
 * @method void setName(string $name)
 * @method string|null getDescription()
 * @method void setDescription(?string $description)
 * @method string|null getHref()
 * @method void setHref(?string $href)
 * @method string|null getIcon()
 * @method void setIcon(?string $icon)
 * @method string|null getIconColor()
 * @method void setIconColor(?string $iconColor)
 * @method string getTarget()
 * @method void setTarget(string $target)
 * @method int getSortOrder()
 * @method void setSortOrder(int $sortOrder)
 * @method string|null getPingUrl()
 * @method void setPingUrl(?string $pingUrl)
 * @method bool getPingEnabled()
 * @method void setPingEnabled(bool $pingEnabled)
 * @method string|null getWidgetType()
 * @method void setWidgetType(?string $widgetType)
 * @method string|null getWidgetConfig()
 * @method void setWidgetConfig(?string $widgetConfig)
 * @method string|null getNotificationOverrides()
 * @method void setNotificationOverrides(?string $notificationOverrides)
 * @method bool getShowScrollbar()
 * @method void setShowScrollbar(bool $showScrollbar)
 * @method string|null getCreatedAt()
 * @method void setCreatedAt(?string $createdAt)
 * @method string|null getUpdatedAt()
 * @method void setUpdatedAt(?string $updatedAt)
 */
class Service extends Entity implements JsonSerializable {

    protected int $categoryId = 0;
    protected string $userId = '';
    protected string $name = '';
    protected ?string $description = null;
    protected ?string $href = null;
    protected ?string $icon = null;
    protected ?string $iconColor = null;
    protected string $target = '_blank';
    protected int $sortOrder = 0;
    protected ?string $pingUrl = null;
    protected bool $pingEnabled = false;
    protected ?string $widgetType = null;
    protected ?string $widgetConfig = null;
    protected ?string $notificationOverrides = null;
    protected bool $showScrollbar = false;
    protected $createdAt = null;
    protected $updatedAt = null;

    public function __construct() {
        $this->addType('id', 'integer');
        $this->addType('categoryId', 'integer');
        $this->addType('sortOrder', 'integer');
        $this->addType('pingEnabled', 'boolean');
        $this->addType('showScrollbar', 'boolean');
    }

    public function jsonSerialize(): array {
        return [
            'id' => $this->getId(),
            'categoryId' => $this->getCategoryId(),
            'userId' => $this->getUserId(),
            'name' => $this->getName(),
            'description' => $this->getDescription(),
            'href' => $this->getHref(),
            'icon' => $this->getIcon(),
            'iconColor' => $this->getIconColor(),
            'target' => $this->getTarget(),
            'sortOrder' => $this->getSortOrder(),
            'pingUrl' => $this->getPingUrl(),
            'pingEnabled' => $this->getPingEnabled(),
            'widgetType' => $this->getWidgetType(),
            'widgetConfig' => $this->getWidgetConfig() ? json_decode($this->getWidgetConfig(), true) : null,
            'notificationOverrides' => $this->getNotificationOverrides() ? json_decode($this->getNotificationOverrides(), true) : null,
            'showScrollbar' => $this->getShowScrollbar(),
            'createdAt' => $this->getCreatedAt(),
            'updatedAt' => $this->getUpdatedAt(),
        ];
    }
}
