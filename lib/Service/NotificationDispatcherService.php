<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Service;

use OCA\LinkBoard\Db\NotificationChannelMapper;
use OCA\LinkBoard\Db\ServiceMapper;
use OCA\LinkBoard\NotificationProvider\NotificationProviderRegistry;
use Psr\Log\LoggerInterface;

class NotificationDispatcherService {

    public function __construct(
        private NotificationChannelMapper $channelMapper,
        private ServiceMapper $serviceMapper,
        private NotificationProviderRegistry $providerRegistry,
        private NotificationService $notificationService,
        private SettingsService $settingsService,
        private LoggerInterface $logger,
    ) {
    }

    public function dispatchOffline(string $userId, int $serviceId, string $serviceName, int $failureCount): void {
        $settings = $this->settingsService->getAll($userId);

        // Nextcloud notification (if enabled)
        if (($settings['notify_nextcloud'] ?? 'true') === 'true') {
            $this->notificationService->sendOfflineNotification($userId, $serviceId, $serviceName, $failureCount);
        }

        $title = 'LinkBoard: ' . $serviceName . ' offline';
        $message = $serviceName . ' has been offline for ' . $failureCount . ' consecutive checks.';
        $this->sendToChannels($userId, $serviceId, $title, $message);
    }

    public function dispatchRecovery(string $userId, int $serviceId, string $serviceName): void {
        $settings = $this->settingsService->getAll($userId);

        if (($settings['notify_nextcloud'] ?? 'true') === 'true') {
            $this->notificationService->sendRecoveryNotification($userId, $serviceId, $serviceName);
        }

        $title = 'LinkBoard: ' . $serviceName . ' recovered';
        $message = $serviceName . ' is back online.';
        $this->sendToChannels($userId, $serviceId, $title, $message);
    }

    /**
     * Send a test notification to a specific channel.
     *
     * @return array{success: bool, error?: string}
     */
    public function testChannel(int $channelId, string $userId): array {
        $channel = $this->channelMapper->findById($channelId, $userId);
        if (!$channel) {
            return ['success' => false, 'error' => 'Channel not found'];
        }

        $provider = $this->providerRegistry->get($channel->getProviderType());
        if (!$provider) {
            return ['success' => false, 'error' => 'Unknown provider: ' . $channel->getProviderType()];
        }

        try {
            $config = json_decode($channel->getConfig(), true) ?: [];
            $config = array_map(fn($v) => is_string($v) ? trim($v) : $v, $config);
            $provider->send($config, 'LinkBoard Test', 'This is a test notification from LinkBoard.');
            return ['success' => true];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function sendToChannels(string $userId, int $serviceId, string $title, string $message): void {
        $channels = $this->channelMapper->findAllByUser($userId);

        // Load per-service notification overrides
        $overrides = [];
        try {
            $service = $this->serviceMapper->findById($serviceId, $userId);
            $raw = $service->getNotificationOverrides();
            if ($raw) {
                $overrides = json_decode($raw, true) ?: [];
            }
        } catch (\Exception $e) {
            // Service not found – fall back to global defaults
        }

        foreach ($channels as $channel) {
            $channelId = (string)$channel->getId();
            if (array_key_exists($channelId, $overrides)) {
                if (!$overrides[$channelId]) { continue; }
            } else {
                if (!$channel->getEnabled()) { continue; }
            }

            $provider = $this->providerRegistry->get($channel->getProviderType());
            if (!$provider) {
                continue;
            }

            try {
                $config = json_decode($channel->getConfig(), true) ?: [];
                $config = array_map(fn($v) => is_string($v) ? trim($v) : $v, $config);
                $provider->send($config, $title, $message);
            } catch (\Throwable $e) {
                $this->logger->warning('LinkBoard: Notification channel "{name}" ({type}) failed: {error}', [
                    'name' => $channel->getName(),
                    'type' => $channel->getProviderType(),
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
