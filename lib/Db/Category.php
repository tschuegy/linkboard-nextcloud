<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Db;

use JsonSerializable;
use OCP\AppFramework\Db\Entity;

/**
 * @method string getUserId()
 * @method void setUserId(string $userId)
 * @method string getName()
 * @method void setName(string $name)
 * @method string getSlug()
 * @method void setSlug(string $slug)
 * @method string|null getIcon()
 * @method void setIcon(?string $icon)
 * @method int getSortOrder()
 * @method void setSortOrder(int $sortOrder)
 * @method bool getCollapsed()
 * @method void setCollapsed(bool $collapsed)
 * @method string|null getTab()
 * @method void setTab(?string $tab)
 * @method int|null getColumns()
 * @method void setColumns(?int $columns)
 * @method int|null getParentId()
 * @method void setParentId(?int $parentId)
 * @method string|null getCreatedAt()
 * @method void setCreatedAt(?string $createdAt)
 * @method string|null getUpdatedAt()
 * @method void setUpdatedAt(?string $updatedAt)
 */
class Category extends Entity implements JsonSerializable {

    protected string $userId = '';
    protected string $name = '';
    protected string $slug = '';
    protected ?string $icon = null;
    protected int $sortOrder = 0;
    protected bool $collapsed = false;
    protected ?string $tab = null;
    protected ?int $columns = null;
    protected ?int $parentId = null;
    protected $createdAt = null;
    protected $updatedAt = null;

    public function __construct() {
        $this->addType('id', 'integer');
        $this->addType('sortOrder', 'integer');
        $this->addType('collapsed', 'boolean');
        $this->addType('columns', 'integer');
        $this->addType('parentId', 'integer');
    }

    public function jsonSerialize(): array {
        return [
            'id' => $this->getId(),
            'userId' => $this->getUserId(),
            'name' => $this->getName(),
            'slug' => $this->getSlug(),
            'icon' => $this->getIcon(),
            'sortOrder' => $this->getSortOrder(),
            'collapsed' => $this->getCollapsed(),
            'tab' => $this->getTab(),
            'columns' => $this->getColumns(),
            'parentId' => $this->getParentId(),
            'createdAt' => $this->getCreatedAt(),
            'updatedAt' => $this->getUpdatedAt(),
        ];
    }
}
