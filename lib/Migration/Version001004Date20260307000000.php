<?php

declare(strict_types=1);

namespace OCA\LinkBoard\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version001004Date20260307000000 extends SimpleMigrationStep {

    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();

        $table = $schema->getTable('linkboard_categories');
        $changed = false;

        if (!$table->hasColumn('parent_id')) {
            $table->addColumn('parent_id', Types::BIGINT, [
                'notnull' => false,
                'unsigned' => true,
                'default' => null,
            ]);
            $table->addIndex(['parent_id'], 'lb_cat_parent_idx');
            $changed = true;
        }

        if (!$table->hasColumn('type')) {
            $table->addColumn('type', Types::STRING, [
                'notnull' => true,
                'length' => 32,
                'default' => 'default',
            ]);
            $changed = true;
        }

        if (!$table->hasColumn('config')) {
            $table->addColumn('config', Types::TEXT, [
                'notnull' => false,
                'default' => null,
            ]);
            $changed = true;
        }

        return $changed ? $schema : null;
    }
}
