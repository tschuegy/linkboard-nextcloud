<?php

declare(strict_types=1);

namespace OCA\LinkBoard\Service;

use OCA\LinkBoard\AppInfo\Application;
use OCP\IAppConfig;
use OCP\IGroupManager;

class GroupAccessService {

	public function __construct(
		private IAppConfig $appConfig,
		private IGroupManager $groupManager,
	) {
	}

	public function isUserAllowed(string $userId): bool {
		$allowedGroups = $this->appConfig->getValueArray(Application::APP_ID, 'allowed_groups', []);
		if (empty($allowedGroups)) {
			return true;
		}
		if ($this->groupManager->isAdmin($userId)) {
			return true;
		}
		foreach ($allowedGroups as $gid) {
			if ($this->groupManager->isInGroup($userId, $gid)) {
				return true;
			}
		}
		return false;
	}
}
