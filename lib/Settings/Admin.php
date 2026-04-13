<?php

declare(strict_types=1);

namespace OCA\LinkBoard\Settings;

use OCA\LinkBoard\AppInfo\Application;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\IAppConfig;
use OCP\IGroupManager;
use OCP\IUserManager;
use OCP\Settings\ISettings;
use OCP\Util;

class Admin implements ISettings {

	public function __construct(
		private IInitialState $initialState,
		private IAppConfig $appConfig,
		private IGroupManager $groupManager,
		private IUserManager $userManager,
	) {
	}

	public function getForm(): TemplateResponse {
		$allowedGroupIds = $this->appConfig->getValueArray(Application::APP_ID, 'allowed_groups', []);
		$allowedGroups = [];
		foreach ($allowedGroupIds as $gid) {
			$group = $this->groupManager->get($gid);
			if ($group !== null) {
				$allowedGroups[] = [
					'id' => $group->getGID(),
					'displayName' => $group->getDisplayName(),
				];
			}
		}

		$statusCheckInterval = $this->appConfig->getValueInt(
			Application::APP_ID, 'status_check_interval', 300
		);

		$globalBoardEnabled = $this->appConfig->getValueBool(Application::APP_ID, 'global_board_enabled', false);
		$globalBoardUserId = $this->appConfig->getValueString(Application::APP_ID, 'global_board_user', '');
		$globalBoardUser = null;
		if ($globalBoardUserId !== '') {
			$user = $this->userManager->get($globalBoardUserId);
			if ($user !== null) {
				$globalBoardUser = [
					'userId' => $globalBoardUserId,
					'displayName' => $user->getDisplayName(),
				];
			}
		}

		$this->initialState->provideInitialState('admin-config', [
			'allowedGroups' => $allowedGroups,
			'statusCheckInterval' => $statusCheckInterval,
			'globalBoardEnabled' => $globalBoardEnabled,
			'globalBoardUser' => $globalBoardUser,
		]);

		Util::addScript('linkboard', 'linkboard-vendors');
		Util::addScript('linkboard', 'linkboard-adminSettings');

		return new TemplateResponse('linkboard', 'adminSettings', [], 'blank');
	}

	public function getSection(): string {
		return 'linkboard';
	}

	public function getPriority(): int {
		return 10;
	}
}
