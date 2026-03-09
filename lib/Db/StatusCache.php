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
 * @method int getConsecutiveFailures()
 * @method void setConsecutiveFailures(int $consecutiveFailures)
 * @method bool getNotified()
 * @method void setNotified(bool $notified)
 */
class StatusCache extends Entity implements JsonSerializable {

    protected int $serviceId = 0;
    protected string $status = 'unknown';
    protected $responseMs = null;
    protected $lastCheck = null;
    protected $details = null;
    protected int $consecutiveFailures = 0;
    protected bool $notified = false;

    public function __construct() {
        $this->addType('id', 'integer');
        $this->addType('serviceId', 'integer');
        $this->addType('responseMs', 'integer');
        $this->addType('consecutiveFailures', 'integer');
        $this->addType('notified', 'boolean');
    }

    public function jsonSerialize(): array {
        return [
            'id' => $this->getId(),
            'serviceId' => $this->getServiceId(),
            'status' => $this->getStatus(),
            'responseMs' => $this->getResponseMs(),
            'lastCheck' => $this->getLastCheck(),
            'details' => $this->getDetails() ? json_decode($this->getDetails(), true) : null,
            'consecutiveFailures' => $this->getConsecutiveFailures(),
            'notified' => $this->getNotified(),
        ];
    }
}
