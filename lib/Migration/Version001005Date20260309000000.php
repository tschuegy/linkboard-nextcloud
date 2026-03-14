<?php

declare(strict_types=1);

namespace OCA\LinkBoard\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version001005Date20260309000000 extends SimpleMigrationStep {

    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();

        $table = $schema->getTable('linkboard_status_cache');
        $changed = false;

        if (!$table->hasColumn('consecutive_failures')) {
            $table->addColumn('consecutive_failures', Types::INTEGER, [
                'notnull' => true,
                'default' => 0,
            ]);
            $changed = true;
        }

        if (!$table->hasColumn('notified')) {
            $table->addColumn('notified', Types::BOOLEAN, [
                'notnull' => false,
                'default' => false,
            ]);
            $changed = true;
        }

        return $changed ? $schema : null;
    }
}
