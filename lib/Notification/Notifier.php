<?php

declare(strict_types=1);

namespace OCA\LinkBoard\Notification;

use OCA\LinkBoard\AppInfo\Application;
use OCP\IURLGenerator;
use OCP\L10N\IFactory;
use OCP\Notification\INotification;
use OCP\Notification\INotifier;

class Notifier implements INotifier {

    public function __construct(
        private IFactory $factory,
        private IURLGenerator $urlGenerator,
    ) {
    }

    public function getID(): string {
        return Application::APP_ID;
    }

    public function getName(): string {
        return 'LinkBoard';
    }

    public function prepare(INotification $notification, string $languageCode): INotification {
        if ($notification->getApp() !== Application::APP_ID) {
            throw new \InvalidArgumentException();
        }

        $l = $this->factory->get(Application::APP_ID, $languageCode);
        $params = $notification->getSubjectParameters();

        switch ($notification->getSubject()) {
            case 'service_offline':
                $notification->setParsedSubject(
                    $l->t('%1$s has been offline for %2$s consecutive checks', [$params['service'], $params['count']])
                );
                $notification->setIcon($this->urlGenerator->imagePath(Application::APP_ID, 'app.svg'));
                break;

            case 'service_recovered':
                $notification->setParsedSubject(
                    $l->t('%1$s is back online', [$params['service']])
                );
                $notification->setIcon($this->urlGenerator->imagePath(Application::APP_ID, 'app.svg'));
                break;

            default:
                throw new \InvalidArgumentException();
        }

        $notification->setLink($this->urlGenerator->linkToRouteAbsolute('linkboard.page.index'));

        return $notification;
    }
}
