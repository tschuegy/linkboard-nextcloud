<?php

declare(strict_types=1);

namespace OCA\LinkBoard\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version001012Date20260715000000 extends SimpleMigrationStep {

    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();
        $changed = false;

        $servicesTable = $schema->getTable('linkboard_services');
        // @phpstan-ignore-next-line -- Doctrine Table is supplied by Nextcloud at runtime.
        if (!$servicesTable->hasColumn('ignore_tls')) {
            // @phpstan-ignore-next-line -- Doctrine Table is supplied by Nextcloud at runtime.
            $servicesTable->addColumn('ignore_tls', Types::BOOLEAN, [
                'notnull' => false,
                'default' => false,
            ]);
            $changed = true;
        }

        return $changed ? $schema : null;
    }
}
