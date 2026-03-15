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
 * @method string getCheckedAt()
 * @method void setCheckedAt(string $checkedAt)
 * @method string|null getDetails()
 * @method void setDetails(?string $details)
 */
class StatusHistory extends Entity implements JsonSerializable {

    protected int $serviceId = 0;
    protected string $status = 'unknown';
    protected $responseMs = null;
    protected string $checkedAt = '';
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
            'checkedAt' => $this->getCheckedAt(),
            'details' => $this->getDetails() ? json_decode($this->getDetails(), true) : null,
        ];
    }
}
