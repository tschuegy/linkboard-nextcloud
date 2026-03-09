<?php
declare(strict_types=1);
namespace OCA\LinkBoard\NotificationProvider;

use OCA\LinkBoard\NotificationProvider\Providers\WebhookProvider;
use OCA\LinkBoard\NotificationProvider\Providers\CallMeBotProvider;
use OCA\LinkBoard\NotificationProvider\Providers\DiscordProvider;
use OCA\LinkBoard\NotificationProvider\Providers\GoogleChatProvider;
use OCA\LinkBoard\NotificationProvider\Providers\GotifyProvider;
use OCA\LinkBoard\NotificationProvider\Providers\HomeAssistantProvider;
use OCA\LinkBoard\NotificationProvider\Providers\MatrixProvider;
use OCA\LinkBoard\NotificationProvider\Providers\MicrosoftTeamsProvider;
use OCA\LinkBoard\NotificationProvider\Providers\NextcloudTalkProvider;
use OCA\LinkBoard\NotificationProvider\Providers\SignalProvider;
use OCA\LinkBoard\NotificationProvider\Providers\SlackProvider;
use OCA\LinkBoard\NotificationProvider\Providers\TelegramProvider;
use OCA\LinkBoard\NotificationProvider\Providers\ThreemaProvider;
use OCA\LinkBoard\NotificationProvider\Providers\NtfyProvider;
use OCA\LinkBoard\NotificationProvider\Providers\PushoverProvider;
use OCA\LinkBoard\NotificationProvider\Providers\BrevoProvider;
use OCA\LinkBoard\NotificationProvider\Providers\SmtpProvider;
use OCA\LinkBoard\NotificationProvider\Providers\ResendProvider;
use OCA\LinkBoard\NotificationProvider\Providers\SendGridProvider;

class NotificationProviderRegistry {

    /** @var array<string, AbstractNotificationProvider> */
    private array $providers = [];

    public function __construct() {
        $this->register(new WebhookProvider());
        $this->register(new CallMeBotProvider());
        $this->register(new DiscordProvider());
        $this->register(new GoogleChatProvider());
        $this->register(new GotifyProvider());
        $this->register(new HomeAssistantProvider());
        $this->register(new MatrixProvider());
        $this->register(new MicrosoftTeamsProvider());
        $this->register(new NextcloudTalkProvider());
        $this->register(new SignalProvider());
        $this->register(new SlackProvider());
        $this->register(new TelegramProvider());
        $this->register(new ThreemaProvider());
        $this->register(new NtfyProvider());
        $this->register(new PushoverProvider());
        $this->register(new BrevoProvider());
        $this->register(new SmtpProvider());
        $this->register(new ResendProvider());
        $this->register(new SendGridProvider());
    }

    private function register(AbstractNotificationProvider $provider): void {
        $this->providers[$provider->getId()] = $provider;
    }

    public function get(string $id): ?AbstractNotificationProvider {
        return $this->providers[$id] ?? null;
    }

    /**
     * @return AbstractNotificationProvider[]
     */
    public function getAll(): array {
        return array_values($this->providers);
    }

    public function getCatalog(): array {
        return array_map(fn($p) => $p->toCatalog(), array_values($this->providers));
    }
}
