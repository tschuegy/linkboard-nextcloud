<?php

declare(strict_types=1);

namespace OCA\LinkBoard\Db;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\AppFramework\Db\QBMapper;
use OCP\IDBConnection;

/**
 * @template-extends QBMapper<Setting>
 */
class SettingMapper extends QBMapper {

    public const DEFAULTS = [
        'title' => 'LinkBoard',
        'theme' => 'auto',
        'background_url' => '',
        'background_blur' => 'md',
        'max_columns' => '4',
        'card_style' => 'default',
        'card_background' => 'glass',
        'status_style' => 'dot',
        'show_search' => 'true',
        'show_category_count' => 'true',
        'check_for_updates' => 'true',
    ];

    public function __construct(IDBConnection $db) {
        parent::__construct($db, 'linkboard_settings', Setting::class);
    }

    /**
     * @return Setting[]
     */
    public function findAllByUser(string $userId): array {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
            ->from($this->getTableName())
            ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)));

        return $this->findEntities($qb);
    }

    /**
     * @throws DoesNotExistException
     * @throws MultipleObjectsReturnedException
     */
    public function findByKey(string $key, string $userId): Setting {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
            ->from($this->getTableName())
            ->where($qb->expr()->eq('key', $qb->createNamedParameter($key)))
            ->andWhere($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)));

        return $this->findEntity($qb);
    }

    /**
     * Get all settings as key=>value map, with defaults applied
     */
    public function getSettingsMap(string $userId): array {
        $settings = self::DEFAULTS;
        $entities = $this->findAllByUser($userId);

        foreach ($entities as $entity) {
            $settings[$entity->getKey()] = $entity->getValue();
        }

        return $settings;
    }

    /**
     * Set a single setting (insert or update)
     */
    public function setSetting(string $key, string $value, string $userId): Setting {
        try {
            $setting = $this->findByKey($key, $userId);
            $setting->setValue($value);
            return $this->update($setting);
        } catch (DoesNotExistException) {
            $setting = new Setting();
            $setting->setUserId($userId);
            $setting->setKey($key);
            $setting->setValue($value);
            return $this->insert($setting);
        }
    }

    /**
     * Delete all settings for a user
     */
    public function deleteAllByUser(string $userId): void {
        $qb = $this->db->getQueryBuilder();
        $qb->delete($this->getTableName())
            ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)));
        $qb->executeStatement();
    }
}
