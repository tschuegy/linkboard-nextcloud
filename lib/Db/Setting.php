<?php

declare(strict_types=1);

namespace OCA\LinkBoard\Db;

use JsonSerializable;
use OCP\AppFramework\Db\Entity;

/**
 * @method string getUserId()
 * @method void setUserId(string $userId)
 * @method string getKey()
 * @method void setKey(string $key)
 * @method string|null getValue()
 * @method void setValue(?string $value)
 */
class Setting extends Entity implements JsonSerializable {

    protected string $userId = '';
    protected string $key = '';
    protected ?string $value = null;

    public function __construct() {
        $this->addType('id', 'integer');
    }

    public function jsonSerialize(): array {
        return [
            'id' => $this->getId(),
            'userId' => $this->getUserId(),
            'key' => $this->getKey(),
            'value' => $this->getValue(),
        ];
    }
}
