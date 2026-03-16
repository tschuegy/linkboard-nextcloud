<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Db;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\QBMapper;
use OCP\IDBConnection;

/**
 * @template-extends QBMapper<NotificationChannel>
 */
class NotificationChannelMapper extends QBMapper {

    public function __construct(IDBConnection $db) {
        parent::__construct($db, 'linkboard_notif_channels', NotificationChannel::class);
    }

    /**
     * @return NotificationChannel[]
     */
    public function findAllByUser(string $userId): array {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
            ->from($this->getTableName())
            ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)));

        return $this->findEntities($qb);
    }

    public function findById(int $id, string $userId): ?NotificationChannel {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
            ->from($this->getTableName())
            ->where($qb->expr()->eq('id', $qb->createNamedParameter($id)))
            ->andWhere($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)));

        try {
            return $this->findEntity($qb);
        } catch (DoesNotExistException) {
            return null;
        }
    }

    public function deleteAllByUser(string $userId): void {
        $qb = $this->db->getQueryBuilder();
        $qb->delete($this->getTableName())
            ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)));
        $qb->executeStatement();
    }
}
