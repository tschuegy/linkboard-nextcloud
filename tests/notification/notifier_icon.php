<?php

declare(strict_types=1);

// SPDX-License-Identifier: AGPL-3.0-or-later

// Regression test for issue #21: Notifier::prepare() must pass an absolute icon
// URL. Since Nextcloud 30 setIcon() rejects relative paths with an
// \InvalidArgumentException, which the server logs as deprecated and which
// skips the setLink() call, so the notification shows without icon and link.

namespace OCP {
    interface IL10N {
        public function t(string $text, $parameters = []): string;
    }

    interface IURLGenerator {
        public function imagePath(string $appName, string $file): string;

        public function getAbsoluteURL(string $url): string;

        public function linkToRouteAbsolute(string $routeName, array $arguments = []): string;
    }
}

namespace OCP\L10N {
    interface IFactory {
        public function get($app, $lang = null, $locale = null);
    }
}

namespace OCP\AppFramework {
    class App {
        public function __construct(string $appName, array $urlParams = []) {
        }
    }
}

namespace OCP\AppFramework\Bootstrap {
    interface IBootstrap {
    }
}

namespace OCP\Notification {
    class UnknownNotificationException extends \Exception {
    }

    class InvalidValueException extends \InvalidArgumentException {
    }

    interface INotification {
        public function getApp(): string;

        public function getSubject(): string;

        public function getSubjectParameters(): array;

        public function setParsedSubject(string $subject): INotification;

        public function setIcon(string $icon): INotification;

        public function setLink(string $link): INotification;
    }

    interface INotifier {
        public function getID(): string;

        public function getName(): string;

        public function prepare(INotification $notification, string $languageCode): INotification;
    }
}

namespace {
    use OCA\LinkBoard\Notification\Notifier;
    use OCP\IL10N;
    use OCP\IURLGenerator;
    use OCP\L10N\IFactory;
    use OCP\Notification\INotification;
    use OCP\Notification\InvalidValueException;
    use OCP\Notification\UnknownNotificationException;

    require dirname(__DIR__, 2) . '/vendor/autoload.php';

    function expectSameValue(mixed $expected, mixed $actual, string $description): void {
        if ($expected !== $actual) {
            throw new \RuntimeException($description . ' (expected ' . var_export($expected, true)
                . ', got ' . var_export($actual, true) . ')');
        }
    }

    final class TestL10N implements IL10N {
        public function t(string $text, $parameters = []): string {
            return vsprintf($text, (array)$parameters);
        }
    }

    final class TestL10NFactory implements IFactory {
        public function get($app, $lang = null, $locale = null) {
            return new TestL10N();
        }
    }

    final class TestURLGenerator implements IURLGenerator {
        public function imagePath(string $appName, string $file): string {
            return '/custom_apps/' . $appName . '/img/' . $file;
        }

        public function getAbsoluteURL(string $url): string {
            return 'https://cloud.example.com' . $url;
        }

        public function linkToRouteAbsolute(string $routeName, array $arguments = []): string {
            return 'https://cloud.example.com/apps/linkboard/';
        }
    }

    // Mirrors the validation of \OC\Notification\Notification::setIcon() in Nextcloud 30+.
    final class TestNotification implements INotification {
        public string $parsedSubject = '';
        public string $icon = '';
        public string $link = '';

        public function __construct(
            private string $app,
            private string $subject,
            private array $subjectParameters,
        ) {
        }

        public function getApp(): string {
            return $this->app;
        }

        public function getSubject(): string {
            return $this->subject;
        }

        public function getSubjectParameters(): array {
            return $this->subjectParameters;
        }

        public function setParsedSubject(string $subject): INotification {
            $this->parsedSubject = $subject;
            return $this;
        }

        public function setIcon(string $icon): INotification {
            if (!str_starts_with($icon, 'http://') && !str_starts_with($icon, 'https://')) {
                throw new InvalidValueException('icon');
            }
            $this->icon = $icon;
            return $this;
        }

        public function setLink(string $link): INotification {
            $this->link = $link;
            return $this;
        }
    }

    $notifier = new Notifier(new TestL10NFactory(), new TestURLGenerator());

    $cases = [
        'service_offline' => ['service' => 'Router', 'count' => 3],
        'service_recovered' => ['service' => 'Router'],
    ];
    foreach ($cases as $subject => $parameters) {
        $notification = new TestNotification('linkboard', $subject, $parameters);
        $notifier->prepare($notification, 'en');

        expectSameValue('https://cloud.example.com/custom_apps/linkboard/img/app.svg', $notification->icon,
            $subject . ' notifications carry an absolute icon URL');
        expectSameValue('https://cloud.example.com/apps/linkboard/', $notification->link,
            $subject . ' notifications link to the board');
    }

    // Notifications of other apps and unknown subjects are rejected with the non-deprecated exception.
    foreach ([['files', 'service_offline'], ['linkboard', 'unknown_subject']] as [$app, $subject]) {
        try {
            $notifier->prepare(new TestNotification($app, $subject, []), 'en');
            throw new \RuntimeException($app . '/' . $subject . ' notifications must be rejected');
        } catch (UnknownNotificationException) {
        }
    }

    echo "Notifier icon checks passed\n";
}
