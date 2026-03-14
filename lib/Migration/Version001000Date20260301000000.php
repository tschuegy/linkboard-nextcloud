<?php

declare(strict_types=1);

namespace OCA\LinkBoard\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version001000Date20260301000000 extends SimpleMigrationStep {

    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
        $schema = $schemaClosure();

        if (!$schema->hasTable('linkboard_categories')) {
            $table = $schema->createTable('linkboard_categories');
            $table->addColumn('id', Types::BIGINT, ['autoincrement' => true, 'notnull' => true, 'unsigned' => true]);
            $table->addColumn('user_id', Types::STRING, ['notnull' => true, 'length' => 64]);
            $table->addColumn('name', Types::STRING, ['notnull' => true, 'length' => 255]);
            $table->addColumn('slug', Types::STRING, ['notnull' => true, 'length' => 255, 'default' => '']);
            $table->addColumn('icon', Types::STRING, ['notnull' => false, 'length' => 512]);
            $table->addColumn('sort_order', Types::INTEGER, ['notnull' => true, 'default' => 0]);
            $table->addColumn('collapsed', Types::BOOLEAN, ['notnull' => false, 'default' => false]);
            $table->addColumn('tab', Types::STRING, ['notnull' => false, 'length' => 128]);
            $table->addColumn('columns', Types::INTEGER, ['notnull' => false, 'default' => null]);
            $table->addColumn('parent_id', Types::BIGINT, ['notnull' => false, 'unsigned' => true, 'default' => null]);
            $table->addColumn('type', Types::STRING, ['notnull' => true, 'length' => 32, 'default' => 'default']);
            $table->addColumn('config', Types::TEXT, ['notnull' => false, 'default' => null]);
            $table->addColumn('created_at', Types::DATETIME, ['notnull' => true]);
            $table->addColumn('updated_at', Types::DATETIME, ['notnull' => true]);
            $table->setPrimaryKey(['id']);
            $table->addIndex(['user_id'], 'lb_cat_user_idx');
            $table->addIndex(['user_id', 'sort_order'], 'lb_cat_user_sort_idx');
            $table->addIndex(['parent_id'], 'lb_cat_parent_idx');
            $table->addUniqueIndex(['user_id', 'slug'], 'lb_cat_user_slug_uniq');
        }

        if (!$schema->hasTable('linkboard_services')) {
            $table = $schema->createTable('linkboard_services');
            $table->addColumn('id', Types::BIGINT, ['autoincrement' => true, 'notnull' => true, 'unsigned' => true]);
            $table->addColumn('category_id', Types::BIGINT, ['notnull' => true, 'unsigned' => true]);
            $table->addColumn('user_id', Types::STRING, ['notnull' => true, 'length' => 64]);
            $table->addColumn('name', Types::STRING, ['notnull' => true, 'length' => 255]);
            $table->addColumn('description', Types::STRING, ['notnull' => false, 'length' => 512]);
            $table->addColumn('href', Types::STRING, ['notnull' => false, 'length' => 1024]);
            $table->addColumn('icon', Types::STRING, ['notnull' => false, 'length' => 512]);
            $table->addColumn('icon_color', Types::STRING, ['notnull' => false, 'length' => 32]);
            $table->addColumn('target', Types::STRING, ['notnull' => true, 'length' => 16, 'default' => '_blank']);
            $table->addColumn('sort_order', Types::INTEGER, ['notnull' => true, 'default' => 0]);
            $table->addColumn('ping_url', Types::STRING, ['notnull' => false, 'length' => 1024]);
            $table->addColumn('ping_enabled', Types::BOOLEAN, ['notnull' => false, 'default' => false]);
            $table->addColumn('widget_type', Types::STRING, ['notnull' => false, 'length' => 64]);
            $table->addColumn('widget_config', Types::TEXT, ['notnull' => false]);
            $table->addColumn('created_at', Types::DATETIME, ['notnull' => true]);
            $table->addColumn('updated_at', Types::DATETIME, ['notnull' => true]);
            $table->setPrimaryKey(['id']);
            $table->addIndex(['user_id'], 'lb_svc_user_idx');
            $table->addIndex(['category_id'], 'lb_svc_cat_idx');
            $table->addIndex(['user_id', 'category_id', 'sort_order'], 'lb_svc_user_cat_sort_idx');
        }

        if (!$schema->hasTable('linkboard_settings')) {
            $table = $schema->createTable('linkboard_settings');
            $table->addColumn('id', Types::BIGINT, ['autoincrement' => true, 'notnull' => true, 'unsigned' => true]);
            $table->addColumn('user_id', Types::STRING, ['notnull' => true, 'length' => 64]);
            $table->addColumn('key', Types::STRING, ['notnull' => true, 'length' => 128]);
            $table->addColumn('value', Types::TEXT, ['notnull' => false]);
            $table->setPrimaryKey(['id']);
            $table->addUniqueIndex(['user_id', 'key'], 'lb_set_user_key_uniq');
        }

        if (!$schema->hasTable('linkboard_status_cache')) {
            $table = $schema->createTable('linkboard_status_cache');
            $table->addColumn('id', Types::BIGINT, ['autoincrement' => true, 'notnull' => true, 'unsigned' => true]);
            $table->addColumn('service_id', Types::BIGINT, ['notnull' => true, 'unsigned' => true]);
            $table->addColumn('status', Types::STRING, ['notnull' => true, 'length' => 16, 'default' => 'unknown']);
            $table->addColumn('response_ms', Types::INTEGER, ['notnull' => false]);
            $table->addColumn('last_check', Types::DATETIME, ['notnull' => false]);
            $table->addColumn('details', Types::TEXT, ['notnull' => false]);
            $table->setPrimaryKey(['id']);
            $table->addUniqueIndex(['service_id'], 'lb_sc_svc_uniq');
        }

        return $schema;
    }
}
