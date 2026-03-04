<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Db;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

/** @template-extends QBMapper<Service> */
class ServiceMapper extends QBMapper {

    public function __construct(IDBConnection $db) {
        parent::__construct($db, 'linkboard_services', Service::class);
    }

    /** @throws DoesNotExistException @throws MultipleObjectsReturnedException */
    public function findById(int $id, string $userId): Service {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
            ->from($this->getTableName())
            ->where($qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT)))
            ->andWhere($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)));
        return $this->findEntity($qb);
    }

    /** @return Service[] */
    public function findAllByUser(string $userId): array {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
            ->from($this->getTableName())
            ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)))
            ->orderBy('sort_order', 'ASC')
            ->addOrderBy('id', 'ASC');
        return $this->findEntities($qb);
    }

    /** @return Service[] */
    public function findByCategory(int $categoryId, string $userId): array {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
            ->from($this->getTableName())
            ->where($qb->expr()->eq('category_id', $qb->createNamedParameter($categoryId, IQueryBuilder::PARAM_INT)))
            ->andWhere($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)))
            ->orderBy('sort_order', 'ASC')
            ->addOrderBy('id', 'ASC');
        return $this->findEntities($qb);
    }

    public function getMaxSortOrder(int $categoryId, string $userId): int {
        $qb = $this->db->getQueryBuilder();
        $qb->select($qb->func()->max('sort_order'))
            ->from($this->getTableName())
            ->where($qb->expr()->eq('category_id', $qb->createNamedParameter($categoryId, IQueryBuilder::PARAM_INT)))
            ->andWhere($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)));
        $result = $qb->executeQuery();
        $maxOrder = $result->fetchOne();
        $result->closeCursor();
        return $maxOrder !== false && $maxOrder !== null ? (int)$maxOrder : -1;
    }

    public function countByUser(string $userId): int {
        $qb = $this->db->getQueryBuilder();
        $qb->select($qb->func()->count('id'))
            ->from($this->getTableName())
            ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)));
        $result = $qb->executeQuery();
        $count = $result->fetchOne();
        $result->closeCursor();
        return (int)$count;
    }

    public function deleteByCategory(int $categoryId, string $userId): void {
        $qb = $this->db->getQueryBuilder();
        $qb->delete($this->getTableName())
            ->where($qb->expr()->eq('category_id', $qb->createNamedParameter($categoryId, IQueryBuilder::PARAM_INT)))
            ->andWhere($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)));
        $qb->executeStatement();
    }

    public function deleteAllByUser(string $userId): void {
        $qb = $this->db->getQueryBuilder();
        $qb->delete($this->getTableName())
            ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)));
        $qb->executeStatement();
    }

    /** Expose DB connection for StatusCheckService */
    public function getDb(): IDBConnection {
        return $this->db;
    }
}
