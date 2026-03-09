<?php

declare(strict_types=1);

namespace OCA\LinkBoard\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version001007Date20260311000000 extends SimpleMigrationStep {

    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();

        $table = $schema->getTable('linkboard_services');
        $changed = false;

        if (!$table->hasColumn('notification_overrides')) {
            $table->addColumn('notification_overrides', Types::TEXT, [
                'notnull' => false,
            ]);
            $changed = true;
        }

        return $changed ? $schema : null;
    }
}
