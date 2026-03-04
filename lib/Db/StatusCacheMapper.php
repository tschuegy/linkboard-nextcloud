<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Db;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

/** @template-extends QBMapper<StatusCache> */
class StatusCacheMapper extends QBMapper {

    public function __construct(IDBConnection $db) {
        parent::__construct($db, 'linkboard_status_cache', StatusCache::class);
    }

    public function findByServiceId(int $serviceId): ?StatusCache {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
            ->from($this->getTableName())
            ->where($qb->expr()->eq('service_id', $qb->createNamedParameter($serviceId, IQueryBuilder::PARAM_INT)));

        try {
            return $this->findEntity($qb);
        } catch (DoesNotExistException) {
            return null;
        }
    }

    /** @return StatusCache[] */
    public function findByServiceIds(array $serviceIds): array {
        if (empty($serviceIds)) return [];

        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
            ->from($this->getTableName())
            ->where($qb->expr()->in('service_id', $qb->createNamedParameter($serviceIds, IQueryBuilder::PARAM_INT_ARRAY)));

        return $this->findEntities($qb);
    }

    /** @return StatusCache[] */
    public function findStale(int $maxAgeSeconds): array {
        $qb = $this->db->getQueryBuilder();
        $cutoff = (new \DateTime())->modify("-{$maxAgeSeconds} seconds")->format('Y-m-d H:i:s');

        $qb->select('*')
            ->from($this->getTableName())
            ->where(
                $qb->expr()->orX(
                    $qb->expr()->isNull('last_check'),
                    $qb->expr()->lt('last_check', $qb->createNamedParameter($cutoff))
                )
            );

        return $this->findEntities($qb);
    }

    public function deleteByServiceId(int $serviceId): void {
        $qb = $this->db->getQueryBuilder();
        $qb->delete($this->getTableName())
            ->where($qb->expr()->eq('service_id', $qb->createNamedParameter($serviceId, IQueryBuilder::PARAM_INT)));
        $qb->executeStatement();
    }
}
