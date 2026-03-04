<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Db;

use JsonSerializable;
use OCP\AppFramework\Db\Entity;

/**
 * @method int getServiceId()
 * @method void setServiceId(int $serviceId)
 * @method string getStatus()
 * @method void setStatus(string $status)
 * @method int|null getResponseMs()
 * @method void setResponseMs(?int $responseMs)
 * @method string|null getLastCheck()
 * @method void setLastCheck(?string $lastCheck)
 * @method string|null getDetails()
 * @method void setDetails(?string $details)
 */
class StatusCache extends Entity implements JsonSerializable {

    protected int $serviceId = 0;
    protected string $status = 'unknown';
    protected $responseMs = null;
    protected $lastCheck = null;
    protected $details = null;

    public function __construct() {
        $this->addType('id', 'integer');
        $this->addType('serviceId', 'integer');
        $this->addType('responseMs', 'integer');
    }

    public function jsonSerialize(): array {
        return [
            'id' => $this->getId(),
            'serviceId' => $this->getServiceId(),
            'status' => $this->getStatus(),
            'responseMs' => $this->getResponseMs(),
            'lastCheck' => $this->getLastCheck(),
            'details' => $this->getDetails() ? json_decode($this->getDetails(), true) : null,
        ];
    }
}
