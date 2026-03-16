<?php

declare(strict_types=1);

namespace OCA\LinkBoard\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version001010Date20260317000000 extends SimpleMigrationStep {

    private IDBConnection $db;
    private array $channelRows = [];
    private array $historyRows = [];
    private bool $migrateChannels = false;
    private bool $migrateHistory = false;

    public function __construct(IDBConnection $db) {
        $this->db = $db;
    }

    public function preSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();

        if ($schema->hasTable('linkboard_notif_channels')) {
            $this->migrateChannels = true;
            $qb = $this->db->getQueryBuilder();
            $qb->select('id', 'user_id', 'name', 'provider_type', 'config', 'enabled')
                ->from('linkboard_notif_channels');
            $result = $qb->executeQuery();
            $this->channelRows = $result->fetchAll();
            $result->free();
        }

        if ($schema->hasTable('linkboard_status_history')) {
            $this->migrateHistory = true;
            $qb = $this->db->getQueryBuilder();
            $qb->select('id', 'service_id', 'status', 'response_ms', 'checked_at', 'details')
                ->from('linkboard_status_history');
            $result = $qb->executeQuery();
            $this->historyRows = $result->fetchAll();
            $result->free();
        }
    }

    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();
        $changed = false;

        if ($this->migrateChannels) {
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

            $schema->dropTable('linkboard_notif_channels');
            $changed = true;
        }

        if ($this->migrateHistory) {
            if (!$schema->hasTable('linkboard_history')) {
                $table = $schema->createTable('linkboard_history');

                $table->addColumn('id', Types::BIGINT, [
                    'autoincrement' => true,
                    'notnull' => true,
                    'unsigned' => true,
                ]);
                $table->addColumn('service_id', Types::BIGINT, [
                    'notnull' => true,
                    'unsigned' => true,
                ]);
                $table->addColumn('status', Types::STRING, [
                    'notnull' => true,
                    'length' => 16,
                ]);
                $table->addColumn('response_ms', Types::INTEGER, [
                    'notnull' => false,
                ]);
                $table->addColumn('checked_at', Types::STRING, [
                    'notnull' => true,
                    'length' => 32,
                ]);
                $table->addColumn('details', Types::TEXT, [
                    'notnull' => false,
                ]);

                $table->setPrimaryKey(['id']);
                $table->addIndex(['service_id', 'checked_at'], 'lb_sh_svc_checked_idx');
            }

            $schema->dropTable('linkboard_status_history');
            $changed = true;
        }

        return $changed ? $schema : null;
    }

    public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
        if ($this->migrateChannels && !empty($this->channelRows)) {
            foreach ($this->channelRows as $row) {
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

        if ($this->migrateHistory && !empty($this->historyRows)) {
            foreach ($this->historyRows as $row) {
                $qb = $this->db->getQueryBuilder();
                $qb->insert('linkboard_history')
                    ->values([
                        'id' => $qb->createNamedParameter($row['id']),
                        'service_id' => $qb->createNamedParameter($row['service_id']),
                        'status' => $qb->createNamedParameter($row['status']),
                        'response_ms' => $qb->createNamedParameter($row['response_ms']),
                        'checked_at' => $qb->createNamedParameter($row['checked_at']),
                        'details' => $qb->createNamedParameter($row['details']),
                    ]);
                $qb->executeStatement();
            }
        }
    }
}
