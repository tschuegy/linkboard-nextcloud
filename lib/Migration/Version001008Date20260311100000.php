<?php

declare(strict_types=1);

namespace OCA\LinkBoard\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version001008Date20260311100000 extends SimpleMigrationStep {

    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();
        $changed = false;

        // A) New table: linkboard_status_history
        if (!$schema->hasTable('linkboard_status_history')) {
            $table = $schema->createTable('linkboard_status_history');
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
            $changed = true;
        }

        // B) New column on linkboard_status_cache: total_failures
        $cacheTable = $schema->getTable('linkboard_status_cache');
        if (!$cacheTable->hasColumn('total_failures')) {
            $cacheTable->addColumn('total_failures', Types::INTEGER, [
                'notnull' => true,
                'default' => 0,
            ]);
            $changed = true;
        }

        return $changed ? $schema : null;
    }
}
