<?php

declare(strict_types=1);

namespace OCA\LinkBoard\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * Makes legacy NOT NULL boolean columns nullable.
 *
 * Releases before 1.4.4 and 1.6.7 created these columns as NOT NULL. The
 * migrations were later fixed, but existing installations kept the old
 * definition, which the Nextcloud 35 schema check reports as a mismatch.
 */
class Version001013Date20260919000000 extends SimpleMigrationStep {

    private const BOOLEAN_COLUMNS = [
        'linkboard_categories' => ['collapsed'],
        'linkboard_services' => ['ping_enabled', 'show_scrollbar', 'ignore_tls'],
        'linkboard_status_cache' => ['notified'],
    ];

    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();
        $changed = false;

        foreach (self::BOOLEAN_COLUMNS as $tableName => $columnNames) {
            if (!$schema->hasTable($tableName)) {
                continue;
            }
            $table = $schema->getTable($tableName);
            foreach ($columnNames as $columnName) {
                // @phpstan-ignore-next-line -- Doctrine Table is supplied by Nextcloud at runtime.
                if (!$table->hasColumn($columnName)) {
                    continue;
                }
                // @phpstan-ignore-next-line -- Doctrine Table is supplied by Nextcloud at runtime.
                $column = $table->getColumn($columnName);
                if ($column->getNotnull()) {
                    $column->setNotnull(false);
                    $changed = true;
                }
            }
        }

        return $changed ? $schema : null;
    }
}
