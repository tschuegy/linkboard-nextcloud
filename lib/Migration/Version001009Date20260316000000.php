<?php

declare(strict_types=1);

namespace OCA\LinkBoard\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version001009Date20260316000000 extends SimpleMigrationStep {

    private IDBConnection $db;
    private array $rows = [];
    private bool $needsMigration = false;

    public function __construct(IDBConnection $db) {
        $this->db = $db;
    }

    public function preSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();

        if (!$schema->hasTable('linkboard_notification_channels')) {
            return;
        }

        $this->needsMigration = true;

        // Read all rows from old table before schema change drops it
        $qb = $this->db->getQueryBuilder();
        $qb->select('id', 'user_id', 'name', 'provider_type', 'config', 'enabled')
            ->from('linkboard_notification_channels');
        $result = $qb->executeQuery();
        $this->rows = $result->fetchAll();
        $result->free();
    }

    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();

        if (!$this->needsMigration) {
            return null;
        }

        if (!$schema->hasTable('linkboard_channels')) {
            $table = $schema->createTable('linkboard_channels');

            $table->addColumn('id', Types::BIGINT, [
                'autoincrement' => true,
                'notnull' => true,
                'unsigned' => true,
            ]);
            $table->addColumn('user_id', Types::STRING, [
                'notnull' => true,
                'length' => 64,
            ]);
            $table->addColumn('name', Types::STRING, [
                'notnull' => true,
                'length' => 255,
            ]);
            $table->addColumn('provider_type', Types::STRING, [
                'notnull' => true,
                'length' => 64,
            ]);
            $table->addColumn('config', Types::TEXT, [
                'notnull' => true,
            ]);
            $table->addColumn('enabled', Types::BOOLEAN, [
                'notnull' => false,
                'default' => true,
            ]);

            $table->setPrimaryKey(['id']);
            $table->addIndex(['user_id'], 'lb_ch_user_idx');
        }

        $schema->dropTable('linkboard_notification_channels');

        return $schema;
    }

    public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
        if (!$this->needsMigration || empty($this->rows)) {
            return;
        }

        // Re-insert saved rows into new table
        foreach ($this->rows as $row) {
            $qb = $this->db->getQueryBuilder();
            $qb->insert('linkboard_channels')
                ->values([
                    'id' => $qb->createNamedParameter($row['id']),
                    'user_id' => $qb->createNamedParameter($row['user_id']),
                    'name' => $qb->createNamedParameter($row['name']),
                    'provider_type' => $qb->createNamedParameter($row['provider_type']),
                    'config' => $qb->createNamedParameter($row['config']),
                    'enabled' => $qb->createNamedParameter($row['enabled']),
                ]);
            $qb->executeStatement();
        }
    }
}
