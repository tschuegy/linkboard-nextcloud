<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget;

// Existing widgets
use OCA\LinkBoard\Widget\Widgets\ProxmoxWidget;
use OCA\LinkBoard\Widget\Widgets\PortainerWidget;
use OCA\LinkBoard\Widget\Widgets\UptimeKumaWidget;
use OCA\LinkBoard\Widget\Widgets\PiHoleWidget;
use OCA\LinkBoard\Widget\Widgets\AdGuardWidget;
use OCA\LinkBoard\Widget\Widgets\TrueNasWidget;
use OCA\LinkBoard\Widget\Widgets\PlexWidget;
use OCA\LinkBoard\Widget\Widgets\JellyfinWidget;
use OCA\LinkBoard\Widget\Widgets\SonarrWidget;
use OCA\LinkBoard\Widget\Widgets\RadarrWidget;
use OCA\LinkBoard\Widget\Widgets\NpmWidget;
use OCA\LinkBoard\Widget\Widgets\HomeAssistantWidget;
use OCA\LinkBoard\Widget\Widgets\CustomApiWidget;
// Batch 1: Arr Stack
use OCA\LinkBoard\Widget\Widgets\BazarrWidget;
use OCA\LinkBoard\Widget\Widgets\LidarrWidget;
use OCA\LinkBoard\Widget\Widgets\ProwlarrWidget;
use OCA\LinkBoard\Widget\Widgets\ReadarrWidget;
use OCA\LinkBoard\Widget\Widgets\Mylar3Widget;
// Batch 2: Media Requests
use OCA\LinkBoard\Widget\Widgets\OverseerrWidget;
use OCA\LinkBoard\Widget\Widgets\JellyseerrWidget;
use OCA\LinkBoard\Widget\Widgets\OmbiWidget;
use OCA\LinkBoard\Widget\Widgets\TautulliWidget;
// Batch 3: Media Servers & Libraries
use OCA\LinkBoard\Widget\Widgets\EmbyWidget;
use OCA\LinkBoard\Widget\Widgets\AudiobookshelfWidget;
use OCA\LinkBoard\Widget\Widgets\NavidromeWidget;
use OCA\LinkBoard\Widget\Widgets\KavitaWidget;
use OCA\LinkBoard\Widget\Widgets\KomgaWidget;
use OCA\LinkBoard\Widget\Widgets\RommWidget;
use OCA\LinkBoard\Widget\Widgets\StashWidget;
use OCA\LinkBoard\Widget\Widgets\CalibreWebWidget;
// Batch 4: Photos, Documents, Notes
use OCA\LinkBoard\Widget\Widgets\ImmichWidget;
use OCA\LinkBoard\Widget\Widgets\PhotoPrismWidget;
use OCA\LinkBoard\Widget\Widgets\PaperlessNgxWidget;
use OCA\LinkBoard\Widget\Widgets\BookloreWidget;
use OCA\LinkBoard\Widget\Widgets\LinkwardenWidget;
use OCA\LinkBoard\Widget\Widgets\TriliumWidget;
// Batch 5: Monitoring (simple)
use OCA\LinkBoard\Widget\Widgets\ScrutinyWidget;
use OCA\LinkBoard\Widget\Widgets\SpeedtestTrackerWidget;
use OCA\LinkBoard\Widget\Widgets\GatusWidget;
use OCA\LinkBoard\Widget\Widgets\HealthchecksWidget;
use OCA\LinkBoard\Widget\Widgets\MySpeedWidget;
use OCA\LinkBoard\Widget\Widgets\BeszelWidget;
use OCA\LinkBoard\Widget\Widgets\NetdataWidget;
use OCA\LinkBoard\Widget\Widgets\GlancesWidget;
// Batch 6: Monitoring (auth)
use OCA\LinkBoard\Widget\Widgets\GrafanaWidget;
use OCA\LinkBoard\Widget\Widgets\PrometheusWidget;
use OCA\LinkBoard\Widget\Widgets\ZabbixWidget;
use OCA\LinkBoard\Widget\Widgets\CheckmkWidget;
use OCA\LinkBoard\Widget\Widgets\CrowdSecWidget;
// Batch 7: Network & DNS
use OCA\LinkBoard\Widget\Widgets\CloudflaredWidget;
use OCA\LinkBoard\Widget\Widgets\NextDnsWidget;
use OCA\LinkBoard\Widget\Widgets\TechnitiumDnsWidget;
use OCA\LinkBoard\Widget\Widgets\TailscaleWidget;
use OCA\LinkBoard\Widget\Widgets\HeadscaleWidget;
use OCA\LinkBoard\Widget\Widgets\TraefikWidget;
use OCA\LinkBoard\Widget\Widgets\WgEasyWidget;
// Batch 8: Network (complex)
use OCA\LinkBoard\Widget\Widgets\MikrotikWidget;
use OCA\LinkBoard\Widget\Widgets\OpenWrtWidget;
use OCA\LinkBoard\Widget\Widgets\OpnSenseWidget;
use OCA\LinkBoard\Widget\Widgets\PfSenseWidget;
use OCA\LinkBoard\Widget\Widgets\FritzboxWidget;
use OCA\LinkBoard\Widget\Widgets\UnifiWidget;
use OCA\LinkBoard\Widget\Widgets\OmadaWidget;
// Batch 9: Download Clients (API key)
use OCA\LinkBoard\Widget\Widgets\SabnzbdWidget;
use OCA\LinkBoard\Widget\Widgets\NzbGetWidget;
use OCA\LinkBoard\Widget\Widgets\FloodWidget;
use OCA\LinkBoard\Widget\Widgets\PyloadWidget;
use OCA\LinkBoard\Widget\Widgets\AutobrrWidget;
// Batch 10: Download Clients (session)
use OCA\LinkBoard\Widget\Widgets\QbittorrentWidget;
use OCA\LinkBoard\Widget\Widgets\TransmissionWidget;
use OCA\LinkBoard\Widget\Widgets\DelugeWidget;
use OCA\LinkBoard\Widget\Widgets\RutorrentWidget;
use OCA\LinkBoard\Widget\Widgets\JdownloaderWidget;
// Batch 11: NAS & Storage
use OCA\LinkBoard\Widget\Widgets\QnapWidget;
use OCA\LinkBoard\Widget\Widgets\DiskStationWidget;
use OCA\LinkBoard\Widget\Widgets\OpenMediaVaultWidget;
use OCA\LinkBoard\Widget\Widgets\UnraidWidget;
use OCA\LinkBoard\Widget\Widgets\KopiaWidget;
use OCA\LinkBoard\Widget\Widgets\BackrestWidget;
use OCA\LinkBoard\Widget\Widgets\FilebrowserWidget;
use OCA\LinkBoard\Widget\Widgets\SyncthingRelayWidget;
use OCA\LinkBoard\Widget\Widgets\DownloadStationWidget;
// Batch 12: Home Automation & IoT
use OCA\LinkBoard\Widget\Widgets\EsphomeWidget;
use OCA\LinkBoard\Widget\Widgets\EvccWidget;
use OCA\LinkBoard\Widget\Widgets\HomebridgeWidget;
use OCA\LinkBoard\Widget\Widgets\HomeBoxWidget;
use OCA\LinkBoard\Widget\Widgets\MoonrakerWidget;
use OCA\LinkBoard\Widget\Widgets\OpenDtuWidget;
use OCA\LinkBoard\Widget\Widgets\PeanutWidget;
use OCA\LinkBoard\Widget\Widgets\ApcUpsWidget;
// Batch 13: Video & Transcoding
use OCA\LinkBoard\Widget\Widgets\FrigateWidget;
use OCA\LinkBoard\Widget\Widgets\TdarrWidget;
use OCA\LinkBoard\Widget\Widgets\FileflowsWidget;
use OCA\LinkBoard\Widget\Widgets\TubeArchivistWidget;
use OCA\LinkBoard\Widget\Widgets\UnmanicWidget;
use OCA\LinkBoard\Widget\Widgets\HdHomeRunWidget;
use OCA\LinkBoard\Widget\Widgets\XteveWidget;
use OCA\LinkBoard\Widget\Widgets\ChannelsDvrWidget;
use OCA\LinkBoard\Widget\Widgets\JellystatWidget;
// Batch 14: Dev, CI/CD & Auth
use OCA\LinkBoard\Widget\Widgets\AuthentikWidget;
use OCA\LinkBoard\Widget\Widgets\GiteaWidget;
use OCA\LinkBoard\Widget\Widgets\GitlabWidget;
use OCA\LinkBoard\Widget\Widgets\ArgoCdWidget;
use OCA\LinkBoard\Widget\Widgets\AzureDevopsWidget;
use OCA\LinkBoard\Widget\Widgets\PterodactylWidget;
use OCA\LinkBoard\Widget\Widgets\KomodoWidget;
use OCA\LinkBoard\Widget\Widgets\CaddyWidget;
// Batch 15: Communication & Social
use OCA\LinkBoard\Widget\Widgets\GotifyWidget;
use OCA\LinkBoard\Widget\Widgets\MastodonWidget;
use OCA\LinkBoard\Widget\Widgets\MailcowWidget;
// Batch 16: Finance & Tracking
use OCA\LinkBoard\Widget\Widgets\FireflyWidget;
use OCA\LinkBoard\Widget\Widgets\GhostfolioWidget;
use OCA\LinkBoard\Widget\Widgets\WallosWidget;
use OCA\LinkBoard\Widget\Widgets\CoinMarketCapWidget;
use OCA\LinkBoard\Widget\Widgets\StocksWidget;
// Batch 17: Food, Tasks & Productivity
use OCA\LinkBoard\Widget\Widgets\MealieWidget;
use OCA\LinkBoard\Widget\Widgets\TandoorWidget;
use OCA\LinkBoard\Widget\Widgets\VikunjaWidget;
use OCA\LinkBoard\Widget\Widgets\PlantItWidget;
use OCA\LinkBoard\Widget\Widgets\FreshRssWidget;
use OCA\LinkBoard\Widget\Widgets\MinifluxWidget;
// Batch 18: Infrastructure
use OCA\LinkBoard\Widget\Widgets\ProxmoxBackupServerWidget;
use OCA\LinkBoard\Widget\Widgets\OctoPrintWidget;
// Batch 19: Various
use OCA\LinkBoard\Widget\Widgets\ChangeDetectionWidget;
use OCA\LinkBoard\Widget\Widgets\WatchtowerWidget;
use OCA\LinkBoard\Widget\Widgets\UptimeRobotWidget;
use OCA\LinkBoard\Widget\Widgets\WhatsUpDockerWidget;
use OCA\LinkBoard\Widget\Widgets\NetAlertXWidget;
use OCA\LinkBoard\Widget\Widgets\NextcloudWidget;
use OCA\LinkBoard\Widget\Widgets\LubeloggerWidget;
use OCA\LinkBoard\Widget\Widgets\KarakeepWidget;
use OCA\LinkBoard\Widget\Widgets\GameDigWidget;
use OCA\LinkBoard\Widget\Widgets\MinecraftWidget;
use OCA\LinkBoard\Widget\Widgets\ArcaneWidget;

/**
 * Registry holding all available widget definitions.
 *
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
class WidgetRegistry {

    /** @var array<string, AbstractWidget> */
    private array $widgets = [];

    public function __construct() {
        // Existing widgets
        $this->register(new ProxmoxWidget());
        $this->register(new PortainerWidget());
        $this->register(new UptimeKumaWidget());
        $this->register(new PiHoleWidget());
        $this->register(new AdGuardWidget());
        $this->register(new TrueNasWidget());
        $this->register(new PlexWidget());
        $this->register(new JellyfinWidget());
        $this->register(new SonarrWidget());
        $this->register(new RadarrWidget());
        $this->register(new NpmWidget());
        $this->register(new HomeAssistantWidget());
        $this->register(new CustomApiWidget());
        // Batch 1: Arr Stack
        $this->register(new BazarrWidget());
        $this->register(new LidarrWidget());
        $this->register(new ProwlarrWidget());
        $this->register(new ReadarrWidget());
        $this->register(new Mylar3Widget());
        // Batch 2: Media Requests
        $this->register(new OverseerrWidget());
        $this->register(new JellyseerrWidget());
        $this->register(new OmbiWidget());
        $this->register(new TautulliWidget());
        // Batch 3: Media Servers & Libraries
        $this->register(new EmbyWidget());
        $this->register(new AudiobookshelfWidget());
        $this->register(new NavidromeWidget());
        $this->register(new KavitaWidget());
        $this->register(new KomgaWidget());
        $this->register(new RommWidget());
        $this->register(new StashWidget());
        $this->register(new CalibreWebWidget());
        // Batch 4: Photos, Documents, Notes
        $this->register(new ImmichWidget());
        $this->register(new PhotoPrismWidget());
        $this->register(new PaperlessNgxWidget());
        $this->register(new BookloreWidget());
        $this->register(new LinkwardenWidget());
        $this->register(new TriliumWidget());
        // Batch 5: Monitoring (simple)
        $this->register(new ScrutinyWidget());
        $this->register(new SpeedtestTrackerWidget());
        $this->register(new GatusWidget());
        $this->register(new HealthchecksWidget());
        $this->register(new MySpeedWidget());
        $this->register(new BeszelWidget());
        $this->register(new NetdataWidget());
        $this->register(new GlancesWidget());
        // Batch 6: Monitoring (auth)
        $this->register(new GrafanaWidget());
        $this->register(new PrometheusWidget());
        $this->register(new ZabbixWidget());
        $this->register(new CheckmkWidget());
        $this->register(new CrowdSecWidget());
        // Batch 7: Network & DNS
        $this->register(new CloudflaredWidget());
        $this->register(new NextDnsWidget());
        $this->register(new TechnitiumDnsWidget());
        $this->register(new TailscaleWidget());
        $this->register(new HeadscaleWidget());
        $this->register(new TraefikWidget());
        $this->register(new WgEasyWidget());
        // Batch 8: Network (complex)
        $this->register(new MikrotikWidget());
        $this->register(new OpenWrtWidget());
        $this->register(new OpnSenseWidget());
        $this->register(new PfSenseWidget());
        $this->register(new FritzboxWidget());
        $this->register(new UnifiWidget());
        $this->register(new OmadaWidget());
        // Batch 9: Download Clients (API key)
        $this->register(new SabnzbdWidget());
        $this->register(new NzbGetWidget());
        $this->register(new FloodWidget());
        $this->register(new PyloadWidget());
        $this->register(new AutobrrWidget());
        // Batch 10: Download Clients (session)
        $this->register(new QbittorrentWidget());
        $this->register(new TransmissionWidget());
        $this->register(new DelugeWidget());
        $this->register(new RutorrentWidget());
        $this->register(new JdownloaderWidget());
        // Batch 11: NAS & Storage
        $this->register(new QnapWidget());
        $this->register(new DiskStationWidget());
        $this->register(new OpenMediaVaultWidget());
        $this->register(new UnraidWidget());
        $this->register(new KopiaWidget());
        $this->register(new BackrestWidget());
        $this->register(new FilebrowserWidget());
        $this->register(new SyncthingRelayWidget());
        $this->register(new DownloadStationWidget());
        // Batch 12: Home Automation & IoT
        $this->register(new EsphomeWidget());
        $this->register(new EvccWidget());
        $this->register(new HomebridgeWidget());
        $this->register(new HomeBoxWidget());
        $this->register(new MoonrakerWidget());
        $this->register(new OpenDtuWidget());
        $this->register(new PeanutWidget());
        $this->register(new ApcUpsWidget());
        // Batch 13: Video & Transcoding
        $this->register(new FrigateWidget());
        $this->register(new TdarrWidget());
        $this->register(new FileflowsWidget());
        $this->register(new TubeArchivistWidget());
        $this->register(new UnmanicWidget());
        $this->register(new HdHomeRunWidget());
        $this->register(new XteveWidget());
        $this->register(new ChannelsDvrWidget());
        $this->register(new JellystatWidget());
        // Batch 14: Dev, CI/CD & Auth
        $this->register(new AuthentikWidget());
        $this->register(new GiteaWidget());
        $this->register(new GitlabWidget());
        $this->register(new ArgoCdWidget());
        $this->register(new AzureDevopsWidget());
        $this->register(new PterodactylWidget());
        $this->register(new KomodoWidget());
        $this->register(new CaddyWidget());
        // Batch 15: Communication & Social
        $this->register(new GotifyWidget());
        $this->register(new MastodonWidget());
        $this->register(new MailcowWidget());
        // Batch 16: Finance & Tracking
        $this->register(new FireflyWidget());
        $this->register(new GhostfolioWidget());
        $this->register(new WallosWidget());
        $this->register(new CoinMarketCapWidget());
        $this->register(new StocksWidget());
        // Batch 17: Food, Tasks & Productivity
        $this->register(new MealieWidget());
        $this->register(new TandoorWidget());
        $this->register(new VikunjaWidget());
        $this->register(new PlantItWidget());
        $this->register(new FreshRssWidget());
        $this->register(new MinifluxWidget());
        // Batch 18: Infrastructure
        $this->register(new ProxmoxBackupServerWidget());
        $this->register(new OctoPrintWidget());
        // Batch 19: Various
        $this->register(new ChangeDetectionWidget());
        $this->register(new WatchtowerWidget());
        $this->register(new UptimeRobotWidget());
        $this->register(new WhatsUpDockerWidget());
        $this->register(new NetAlertXWidget());
        $this->register(new NextcloudWidget());
        $this->register(new LubeloggerWidget());
        $this->register(new KarakeepWidget());
        $this->register(new GameDigWidget());
        $this->register(new MinecraftWidget());
        $this->register(new ArcaneWidget());
    }

    public function register(AbstractWidget $widget): void {
        $this->widgets[$widget->getId()] = $widget;
    }

    public function get(string $id): ?AbstractWidget {
        return $this->widgets[$id] ?? null;
    }

    /** @return AbstractWidget[] */
    public function getAll(): array {
        return array_values($this->widgets);
    }

    /**
     * Catalog for the frontend (no secrets).
     * @return array<array>
     */
    public function getCatalog(): array {
        $catalog = array_map(
            fn(AbstractWidget $w) => $w->toCatalog(),
            array_values($this->widgets)
        );
        usort($catalog, fn(array $a, array $b) => strcasecmp($a['label'], $b['label']));
        return $catalog;
    }
}
