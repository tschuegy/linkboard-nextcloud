# LinkBoard Widgets

[Back to README](README.md)

LinkBoard ships with **135 built-in widgets** that display real-time data from self-hosted services. Each widget is configured per-service in the service editor. The table below lists every available widget, its internal ID, and the configuration fields it accepts.

| Widget | ID | Configuration | Tested |
|---|---|---|---|
| AdGuard Home | `adguard` | `username` (required), `password` (required) | [x] |
| APC UPS | `apcups` | — | [ ] |
| Arcane | `arcane` | `api_key` (required) | [ ] |
| ArgoCD | `argocd` | `api_key` (required) | [ ] |
| Audiobookshelf | `audiobookshelf` | `api_key` (required) | [ ] |
| Authentik | `authentik` | `api_key` (required) | [ ] |
| Autobrr | `autobrr` | `api_key` (required) | [ ] |
| Azure DevOps | `azuredevops` | `api_key` (required), `organization` (required), `project` (required) | [ ] |
| Backrest | `backrest` | `api_key` (optional) | [ ] |
| Bazarr | `bazarr` | `api_key` (required) | [ ] |
| Beszel | `beszel` | `api_key` (optional) | [ ] |
| Booklore | `booklore` | `api_key` (optional) | [ ] |
| Caddy | `caddy` | — | [ ] |
| Calibre-Web | `calibreweb` | `username` (required), `password` (required) | [ ] |
| ChangeDetection.io | `changedetectionio` | `api_key` (optional) | [ ] |
| Channels DVR | `channelsdvr` | — | [ ] |
| Checkmk | `checkmk` | `api_key` (required), `username` (required), `site` (required) | [ ] |
| Cloudflared | `cloudflared` | `api_key` (required), `account_id` (required), `tunnel_id` (required) | [ ] |
| CoinMarketCap | `coinmarketcap` | `api_key` (required), `symbol` (required) | [ ] |
| CrowdSec | `crowdsec` | `api_key` (required) | [ ] |
| Custom API | `customapi` | `url` (optional), `method` (optional), `auth_header` (optional), `mappings` (required) | [x] |
| Deluge | `deluge` | `password` (required) | [ ] |
| DiskStation (Synology) | `diskstation` | `username` (required), `password` (required) | [ ] |
| Download Station | `downloadstation` | `username` (required), `password` (required) | [ ] |
| Emby | `emby` | `api_key` (required) | [ ] |
| ESPHome | `esphome` | — | [ ] |
| EVCC | `evcc` | — | [ ] |
| Filebrowser | `filebrowser` | `username` (required), `password` (required) | [ ] |
| FileFlows | `fileflows` | — | [ ] |
| Firefly III | `firefly` | `api_key` (required) | [ ] |
| Flood | `flood` | `username` (required), `password` (required) | [ ] |
| FreshRSS | `freshrss` | `username` (required), `password` (required) | [ ] |
| Frigate | `frigate` | — | [ ] |
| Fritz!Box | `fritzbox` | `username` (optional), `password` (required) | [ ] |
| GameDig | `gamedig` | — | [ ] |
| Gatus | `gatus` | — | [ ] |
| Ghostfolio | `ghostfolio` | `api_key` (required) | [ ] |
| Gitea | `gitea` | `api_key` (required) | [ ] |
| GitLab | `gitlab` | `api_key` (required) | [ ] |
| Glances | `glances` | — | [ ] |
| Gotify | `gotify` | `api_key` (required) | [ ] |
| Grafana | `grafana` | `username` (required), `password` (required) | [ ] |
| HDHomeRun | `hdhomerun` | — | [ ] |
| Headscale | `headscale` | `api_key` (required) | [ ] |
| Healthchecks | `healthchecks` | `api_key` (required) | [ ] |
| Home Assistant | `homeassistant` | `token` (required) | [ ] |
| HomeBox | `homebox` | `username` (required), `password` (required) | [ ] |
| Homebridge | `homebridge` | `username` (required), `password` (required) | [ ] |
| Immich | `immich` | `api_key` (required) | [x] |
| JDownloader | `jdownloader` | `username` (required), `password` (required), `device` (optional) | [ ] |
| Jellyfin | `jellyfin` | `api_key` (required) | [ ] |
| Jellyseerr | `jellyseerr` | `api_key` (required) | [ ] |
| Jellystat | `jellystat` | `api_key` (required) | [ ] |
| Karakeep | `karakeep` | `api_key` (required) | [ ] |
| Kavita | `kavita` | `username` (required), `password` (required) | [ ] |
| Komga | `komga` | `username` (required), `password` (required) | [ ] |
| Komodo | `komodo` | `api_key` (required) | [ ] |
| Kopia | `kopia` | `username` (optional), `password` (optional) | [ ] |
| Lidarr | `lidarr` | `api_key` (required) | [ ] |
| Linkwarden | `linkwarden` | `api_key` (required) | [ ] |
| LubeLogger | `lubelogger` | `username` (required), `password` (required) | [ ] |
| Mailcow | `mailcow` | `api_key` (required) | [ ] |
| Mastodon | `mastodon` | `api_key` (required) | [ ] |
| Mealie | `mealie` | `api_key` (required) | [ ] |
| Mikrotik | `mikrotik` | `username` (required), `password` (required) | [x] |
| Minecraft | `minecraft` | — | [ ] |
| Miniflux | `miniflux` | `api_key` (required) | [ ] |
| Moonraker | `moonraker` | `api_key` (optional) | [ ] |
| Mylar3 | `mylar3` | `api_key` (required) | [ ] |
| MySpeed | `myspeed` | — | [ ] |
| Navidrome | `navidrome` | `username` (required), `password` (required) | [ ] |
| NetAlertX | `netalertx` | — | [ ] |
| Netdata | `netdata` | — | [ ] |
| Nextcloud | `nextcloud` | `username` (required), `password` (required) | [ ] |
| NextDNS | `nextdns` | `api_key` (required), `profile_id` (required) | [ ] |
| Nginx Proxy Manager | `npm` | `email` (required), `password` (required) | [ ] |
| NZBGet | `nzbget` | `username` (required), `password` (required) | [ ] |
| OctoPrint | `octoprint` | `api_key` (required) | [ ] |
| Omada | `omada` | `username` (required), `password` (required), `site` (optional) | [ ] |
| Ombi | `ombi` | `api_key` (required) | [ ] |
| OpenDTU | `opendtu` | — | [ ] |
| OpenMediaVault | `openmediavault` | `username` (required), `password` (required) | [ ] |
| OpenWRT | `openwrt` | `username` (required), `password` (required) | [ ] |
| OPNsense | `opnsense` | `username` (required), `password` (required) | [ ] |
| Overseerr | `overseerr` | `api_key` (required) | [ ] |
| Paperless-ngx | `paperlessngx` | `api_key` (optional), `username` (optional), `password` (optional) | [x] |
| Peanut (NUT UPS) | `peanut` | — | [ ] |
| pfSense | `pfsense` | `username` (required), `password` (required) | [ ] |
| PhotoPrism | `photoprism` | `username` (optional), `password` (required) | [ ] |
| Pi-hole | `pihole` | `api_token` (optional) | [ ] |
| Plant-It | `plantit` | `api_key` (required) | [ ] |
| Plex | `plex` | `token` (required) | [ ] |
| Portainer | `portainer` | `api_key` (required), `env` (optional) | [ ] |
| Prometheus | `prometheus` | `api_key` (optional) | [ ] |
| Prowlarr | `prowlarr` | `api_key` (required) | [ ] |
| Proxmox Backup Server | `proxmoxbackupserver` | `api_token` (required) — Format: `user@realm!tokenid:secret`. Token needs `Audit` role on path `/` with Propagate. Service URL must use `https://`. | [x] |
| Proxmox VE | `proxmox` | `api_token` (required) — Format: `user@realm!tokenid=UUID`. Token needs `PVEAuditor` role on path `/`. Service URL must use `https://`. | [x] |
| Pterodactyl | `pterodactyl` | `api_key` (required) | [ ] |
| pyLoad | `pyload` | `username` (required), `password` (required) | [ ] |
| qBittorrent | `qbittorrent` | `username` (required), `password` (required) | [ ] |
| QNAP | `qnap` | `username` (required), `password` (required) | [ ] |
| Radarr | `radarr` | `api_key` (required) | [ ] |
| Readarr | `readarr` | `api_key` (required) | [ ] |
| ROMM | `romm` | `username` (required), `password` (required) | [ ] |
| ruTorrent | `rutorrent` | `username` (required), `password` (required) | [ ] |
| SABnzbd | `sabnzbd` | `api_key` (required) | [ ] |
| Scrutiny | `scrutiny` | — | [ ] |
| Sonarr | `sonarr` | `api_key` (required) | [ ] |
| Speedtest Tracker | `speedtesttracker` | `api_key` (optional) | [ ] |
| Stash | `stash` | `api_key` (required) | [ ] |
| Stocks | `stocks` | `api_key` (required), `symbol` (required) | [ ] |
| Syncthing Relay | `syncthingrelay` | — | [ ] |
| System Resources | `resources` | `diskPaths` (optional), `tempUnit` (optional) | [ ] |
| Tailscale | `tailscale` | `api_key` (required), `tailnet` (required) | [ ] |
| Tandoor | `tandoor` | `api_key` (required) | [ ] |
| Tautulli | `tautulli` | `api_key` (required) | [ ] |
| Tdarr | `tdarr` | — | [ ] |
| Technitium DNS | `technitiumdns` | `api_key` (required) | [ ] |
| Traefik | `traefik` | `username` (optional), `password` (optional) | [ ] |
| Transmission | `transmission` | `username` (optional), `password` (optional) | [ ] |
| Trilium | `trilium` | `api_key` (required) — shows version, DB version, note count | [x] |
| TrueNAS | `truenas` | `api_key` (required) | [ ] |
| TubeArchivist | `tubearchivist` | `api_key` (required) | [ ] |
| UniFi Controller | `unifi` | `controllerType` (select: `UniFi OS (UDM, Cloud Key Gen2+)` / `Legacy Controller`, default UniFi OS), `username` (required), `password` (required), `site` (optional). **Note:** `unifi.ui.com` is not supported — use your local controller IP. | [ ] |
| Unmanic | `unmanic` | — | [ ] |
| Unraid | `unraid` | `api_key` (required) | [ ] |
| Uptime Kuma | `uptimekuma` | `slug` (required) | [x] |
| UptimeRobot | `uptimerobot` | `api_key` (required) | [ ] |
| Vikunja | `vikunja` | `api_key` (required) | [ ] |
| Wallos | `wallos` | — | [ ] |
| Watchtower | `watchtower` | `api_key` (required) | [ ] |
| WG-Easy | `wgeasy` | `password` (required) | [ ] |
| What's Up Docker | `whatsupdocker` | — | [ ] |
| xTeVe | `xteve` | — | [ ] |
| Zabbix | `zabbix` | `api_key` (required) | [ ] |
