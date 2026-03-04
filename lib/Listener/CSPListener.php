<?php

declare(strict_types=1);

namespace OCA\LinkBoard\Listener;

use OCA\LinkBoard\Db\ServiceMapper;
use OCA\LinkBoard\Db\SettingMapper;
use OCP\AppFramework\Http\EmptyContentSecurityPolicy;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\IUserSession;
use OCP\Security\CSP\AddContentSecurityPolicyEvent;

/** @template-implements IEventListener<AddContentSecurityPolicyEvent> */
class CSPListener implements IEventListener {
	public function __construct(
		private IUserSession $userSession,
		private SettingMapper $settingMapper,
		private ServiceMapper $serviceMapper,
	) {
	}

	public function handle(Event $event): void {
		if (!($event instanceof AddContentSecurityPolicyEvent)) {
			return;
		}
		$csp = new EmptyContentSecurityPolicy();
		$csp->addAllowedImageDomain('https://cdn.jsdelivr.net');

		$user = $this->userSession->getUser();
		if ($user !== null) {
			$userId = $user->getUID();

			// Auto-extract domains from service icon URLs
			$services = $this->serviceMapper->findAllByUser($userId);
			$seen = [];
			foreach ($services as $service) {
				$icon = $service->getIcon();
				if ($icon !== null && str_starts_with($icon, 'https://')) {
					$host = parse_url($icon, PHP_URL_HOST);
					if ($host !== null && $host !== false && !isset($seen[$host])) {
						$seen[$host] = true;
						$csp->addAllowedImageDomain('https://' . $host);
					}
				}
			}

			// Auto-extract domain from background image URL
			$settings = $this->settingMapper->getSettingsMap($userId);
			$bgUrl = $settings['background_url'] ?? '';
			if ($bgUrl !== '' && str_starts_with($bgUrl, 'https://')) {
				$host = parse_url($bgUrl, PHP_URL_HOST);
				if ($host !== null && $host !== false && !isset($seen[$host])) {
					$seen[$host] = true;
					$csp->addAllowedImageDomain('https://' . $host);
				}
			}
		}

		$event->addPolicy($csp);
	}
}
