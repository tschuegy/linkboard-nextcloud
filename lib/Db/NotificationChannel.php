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
 * @method string getProviderType()
 * @method void setProviderType(string $providerType)
 * @method string getConfig()
 * @method void setConfig(string $config)
 * @method bool getEnabled()
 * @method void setEnabled(bool $enabled)
 */
class NotificationChannel extends Entity implements JsonSerializable {

    protected string $userId = '';
    protected string $name = '';
    protected string $providerType = '';
    protected string $config = '{}';
    protected bool $enabled = true;

    public function __construct() {
        $this->addType('id', 'integer');
        $this->addType('enabled', 'boolean');
    }

    public function jsonSerialize(): array {
        return [
            'id' => $this->getId(),
            'userId' => $this->getUserId(),
            'name' => $this->getName(),
            'providerType' => $this->getProviderType(),
            'config' => json_decode($this->getConfig(), true) ?: [],
            'enabled' => $this->getEnabled(),
        ];
    }
}
