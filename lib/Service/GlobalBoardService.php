<?php

declare(strict_types=1);

namespace OCA\LinkBoard\Service;

use OCA\LinkBoard\AppInfo\Application;
use OCP\IAppConfig;
use OCP\IGroupManager;
use OCP\IUserManager;

class GlobalBoardService {

    public function __construct(
        private IAppConfig $appConfig,
        private IGroupManager $groupManager,
        private IUserManager $userManager,
    ) {
    }

    public function isEnabled(): bool {
        return $this->appConfig->getValueBool(Application::APP_ID, 'global_board_enabled', false);
    }

    public function getSourceUserId(): ?string {
        $userId = $this->appConfig->getValueString(Application::APP_ID, 'global_board_user', '');
        if ($userId === '' || $this->userManager->get($userId) === null) {
            return null;
        }
        return $userId;
    }

    /**
     * Resolve the effective userId for data loading and whether the current user can edit.
     * @return array{sourceUserId: string, canEdit: bool, globalBoardActive: bool}
     */
    public function resolve(string $currentUserId): array {
        if ($this->isEnabled()) {
            $sourceUserId = $this->getSourceUserId();
            if ($sourceUserId !== null) {
                return [
                    'sourceUserId' => $sourceUserId,
                    'canEdit' => $this->groupManager->isAdmin($currentUserId),
                    'globalBoardActive' => true,
                ];
            }
        }
        return [
            'sourceUserId' => $currentUserId,
            'canEdit' => true,
            'globalBoardActive' => false,
        ];
    }
}
