<?php

declare(strict_types=1);

namespace OCA\LinkBoard\Middleware;

use OCA\LinkBoard\Controller\SettingsApiController;
use OCA\LinkBoard\Service\GroupAccessService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Middleware;
use OCP\IURLGenerator;

class GroupRestrictionMiddleware extends Middleware {

	public function __construct(
		private GroupAccessService $accessService,
		private IURLGenerator $urlGenerator,
		private ?string $userId,
	) {
	}

	public function beforeController(Controller $controller, string $methodName): void {
		// Admin settings endpoints are admin-only anyway – skip
		if ($controller instanceof SettingsApiController
			&& in_array($methodName, ['getAdminSettings', 'updateAdminSettings', 'searchGroups', 'listBoards'], true)) {
			return;
		}

		if ($this->userId === null) {
			return;
		}

		if (!$this->accessService->isUserAllowed($this->userId)) {
			throw new GroupRestrictionException();
		}
	}

	public function afterException(Controller $controller, string $methodName, \Exception $exception): JSONResponse|RedirectResponse {
		if ($exception instanceof GroupRestrictionException) {
			// API requests get 403, page requests get redirected
			if (str_contains($methodName, 'index') && !str_contains(strtolower(get_class($controller)), 'api')) {
				return new RedirectResponse($this->urlGenerator->linkToDefaultPageUrl());
			}
			return new JSONResponse(['message' => 'Access restricted'], Http::STATUS_FORBIDDEN);
		}
		throw $exception;
	}
}
