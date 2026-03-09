<?php

declare(strict_types=1);

namespace OCA\LinkBoard\Service;

use OCA\LinkBoard\AppInfo\Application;
use OCP\Notification\IManager;

class NotificationService {

    public function __construct(
        private IManager $notificationManager,
    ) {
    }

    public function sendOfflineNotification(string $userId, int $serviceId, string $serviceName, int $failureCount): void {
        $notification = $this->notificationManager->createNotification();
        $notification->setApp(Application::APP_ID)
            ->setUser($userId)
            ->setDateTime(new \DateTime())
            ->setObject('service', (string)$serviceId)
            ->setSubject('service_offline', [
                'service' => $serviceName,
                'count' => $failureCount,
            ]);

        $this->notificationManager->notify($notification);
    }

    public function sendRecoveryNotification(string $userId, int $serviceId, string $serviceName): void {
        $notification = $this->notificationManager->createNotification();
        $notification->setApp(Application::APP_ID)
            ->setUser($userId)
            ->setDateTime(new \DateTime())
            ->setObject('service', (string)$serviceId)
            ->setSubject('service_recovered', [
                'service' => $serviceName,
            ]);

        $this->notificationManager->notify($notification);
    }

    public function clearOfflineNotifications(string $userId, int $serviceId): void {
        $notification = $this->notificationManager->createNotification();
        $notification->setApp(Application::APP_ID)
            ->setUser($userId)
            ->setObject('service', (string)$serviceId)
            ->setSubject('service_offline');

        $this->notificationManager->markProcessed($notification);
    }
}
