<?php

declare(strict_types=1);

namespace OCA\LinkBoard\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version001006Date20260310000000 extends SimpleMigrationStep {

    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();

        if (!$schema->hasTable('linkboard_notification_channels')) {
            $table = $schema->createTable('linkboard_notification_channels');

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
                'notnull' => true,
                'default' => true,
            ]);

            $table->setPrimaryKey(['id']);
            $table->addIndex(['user_id'], 'lb_nc_user_idx');

            return $schema;
        }

        return null;
    }
}
