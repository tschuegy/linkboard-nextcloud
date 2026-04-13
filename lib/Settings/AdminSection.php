<?php

declare(strict_types=1);

namespace OCA\LinkBoard\Settings;

use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\Settings\IIconSection;

class AdminSection implements IIconSection {

	public function __construct(
		private IL10N $l,
		private IURLGenerator $urlGenerator,
	) {
	}

	public function getID(): string {
		return 'linkboard';
	}

	public function getName(): string {
		return $this->l->t('LinkBoard');
	}

	public function getPriority(): int {
		return 90;
	}

	public function getIcon(): string {
		return $this->urlGenerator->imagePath('linkboard', 'app-dark.svg');
	}
}
