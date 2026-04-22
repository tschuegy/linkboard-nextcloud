<?php

declare(strict_types=1);

namespace OCA\LinkBoard\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version001011Date20260421000000 extends SimpleMigrationStep {

    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();
        $changed = false;

        $servicesTable = $schema->getTable('linkboard_services');
        if (!$servicesTable->hasColumn('show_scrollbar')) {
            $servicesTable->addColumn('show_scrollbar', Types::BOOLEAN, [
                'notnull' => true,
                'default' => false,
            ]);
            $changed = true;
        }

        return $changed ? $schema : null;
    }
}
