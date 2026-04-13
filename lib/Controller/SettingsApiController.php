<?php

declare(strict_types=1);

namespace OCA\LinkBoard\Controller;

use OCA\LinkBoard\AppInfo\Application;
use OCA\LinkBoard\Db\CategoryMapper;
use OCA\LinkBoard\Service\SettingsService;
use OCP\AppFramework\ApiController;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\IAppConfig;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\IUserManager;

class SettingsApiController extends ApiController {

    public function __construct(
        IRequest $request,
        private SettingsService $settingsService,
        private IAppConfig $appConfig,
        private IGroupManager $groupManager,
        private IUserManager $userManager,
        private CategoryMapper $categoryMapper,
        private ?string $userId,
    ) {
        parent::__construct(Application::APP_ID, $request);
    }

    #[NoAdminRequired]
    public function index(): DataResponse {
        $settings = $this->settingsService->getAll($this->userId);
        return new DataResponse($settings);
    }

    #[NoAdminRequired]
    public function updateAll(array $settings): DataResponse {
        $this->settingsService->setMultiple($settings, $this->userId);
        $allSettings = $this->settingsService->getAll($this->userId);
        return new DataResponse($allSettings);
    }

    #[NoAdminRequired]
    public function updateSingle(string $key, string $value): DataResponse {
        $this->settingsService->set($key, $value, $this->userId);
        return new DataResponse(['key' => $key, 'value' => $value]);
    }

    /** Any user can read admin settings (needed for display) */
    #[NoAdminRequired]
    public function getAdminSettings(): DataResponse {
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

        return new DataResponse([
            'status_check_interval' => $this->appConfig->getValueInt(Application::APP_ID, 'status_check_interval', 300),
            'allowed_groups' => $allowedGroups,
            'global_board_enabled' => $globalBoardEnabled,
            'global_board_user' => $globalBoardUser,
        ]);
    }

    /** Only admins can update admin settings (no #[NoAdminRequired]) */
    public function updateAdminSettings(
        int $statusCheckInterval,
        ?array $allowedGroups = null,
        ?bool $globalBoardEnabled = null,
        ?string $globalBoardUser = null,
    ): DataResponse {
        $statusCheckInterval = max(60, min(1800, $statusCheckInterval));
        $this->appConfig->setValueInt(Application::APP_ID, 'status_check_interval', $statusCheckInterval);

        if ($allowedGroups !== null) {
            $validGroupIds = [];
            foreach ($allowedGroups as $gid) {
                if ($this->groupManager->get($gid) !== null) {
                    $validGroupIds[] = $gid;
                }
            }
            $this->appConfig->setValueArray(Application::APP_ID, 'allowed_groups', $validGroupIds);
        }

        if ($globalBoardEnabled !== null) {
            $this->appConfig->setValueBool(Application::APP_ID, 'global_board_enabled', $globalBoardEnabled);
        }
        if ($globalBoardUser !== null) {
            if ($globalBoardUser === '' || $this->userManager->get($globalBoardUser) !== null) {
                $this->appConfig->setValueString(Application::APP_ID, 'global_board_user', $globalBoardUser);
            }
        }

        return new DataResponse([
            'status_check_interval' => $statusCheckInterval,
            'allowed_groups' => $allowedGroups ?? $this->appConfig->getValueArray(Application::APP_ID, 'allowed_groups', []),
            'global_board_enabled' => $this->appConfig->getValueBool(Application::APP_ID, 'global_board_enabled', false),
            'global_board_user' => $this->appConfig->getValueString(Application::APP_ID, 'global_board_user', ''),
        ]);
    }

    /** List all users that have LinkBoard categories (admin-only) */
    public function listBoards(): DataResponse {
        $usersWithCategories = $this->categoryMapper->getUsersWithCategories();
        $result = [];
        foreach ($usersWithCategories as $row) {
            $user = $this->userManager->get($row['user_id']);
            if ($user !== null) {
                $result[] = [
                    'userId' => $row['user_id'],
                    'displayName' => $user->getDisplayName(),
                    'categoryCount' => (int)$row['category_count'],
                ];
            }
        }
        return new DataResponse($result);
    }

    /** Search groups for the admin group selector */
    public function searchGroups(string $search = ''): DataResponse {
        $groups = $this->groupManager->search($search, 50);
        $result = [];
        foreach ($groups as $group) {
            $result[] = [
                'id' => $group->getGID(),
                'displayName' => $group->getDisplayName(),
            ];
        }
        return new DataResponse($result);
    }
}
