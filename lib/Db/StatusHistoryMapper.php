<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Db;

use DateTime;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

/** @template-extends QBMapper<StatusHistory> */
class StatusHistoryMapper extends QBMapper {

    public function __construct(IDBConnection $db) {
        parent::__construct($db, 'linkboard_history', StatusHistory::class);
    }

    /**
     * @return StatusHistory[]
     */
    public function findByServiceId(int $serviceId, string $period = '24h'): array {
        $cutoff = $this->calculateCutoff($period);

        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
            ->from($this->getTableName())
            ->where($qb->expr()->eq('service_id', $qb->createNamedParameter($serviceId, IQueryBuilder::PARAM_INT)))
            ->andWhere($qb->expr()->gte('checked_at', $qb->createNamedParameter($cutoff)))
            ->orderBy('checked_at', 'ASC');

        return $this->findEntities($qb);
    }

    public function deleteOlderThan(int $days = 7): int {
        $cutoff = (new DateTime())->modify("-{$days} days")->format('Y-m-d H:i:s');

        $qb = $this->db->getQueryBuilder();
        $qb->delete($this->getTableName())
            ->where($qb->expr()->lt('checked_at', $qb->createNamedParameter($cutoff)));

        return $qb->executeStatement();
    }

    /**
     * @return array<int, StatusHistory[]> keyed by serviceId, each oldest→newest
     */
    public function findRecentByServiceIds(array $serviceIds, int $limit = 10): array {
        if (empty($serviceIds)) {
            return [];
        }

        $cutoff = (new \DateTime())->modify('-24 hours')->format('Y-m-d H:i:s');

        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
            ->from($this->getTableName())
            ->where($qb->expr()->in('service_id', $qb->createNamedParameter($serviceIds, IQueryBuilder::PARAM_INT_ARRAY)))
            ->andWhere($qb->expr()->gte('checked_at', $qb->createNamedParameter($cutoff)))
            ->orderBy('service_id', 'ASC')
            ->addOrderBy('checked_at', 'DESC');

        $entities = $this->findEntities($qb);

        // Group by serviceId, take first $limit per group, then reverse for oldest→newest
        $grouped = [];
        $counts = [];
        foreach ($entities as $entity) {
            $sid = $entity->getServiceId();
            if (!isset($counts[$sid])) {
                $counts[$sid] = 0;
            }
            if ($counts[$sid] < $limit) {
                $grouped[$sid][] = $entity;
                $counts[$sid]++;
            }
        }

        // Reverse each group so oldest is first (left→right display)
        foreach ($grouped as $sid => $entries) {
            $grouped[$sid] = array_reverse($entries);
        }

        return $grouped;
    }

    public function deleteByServiceId(int $serviceId): void {
        $qb = $this->db->getQueryBuilder();
        $qb->delete($this->getTableName())
            ->where($qb->expr()->eq('service_id', $qb->createNamedParameter($serviceId, IQueryBuilder::PARAM_INT)));
        $qb->executeStatement();
    }

    private function calculateCutoff(string $period): string {
        $modifier = match ($period) {
            '1h' => '-1 hour',
            '3h' => '-3 hours',
            '7d' => '-7 days',
            default => '-24 hours',
        };
        return (new DateTime())->modify($modifier)->format('Y-m-d H:i:s');
    }
}
