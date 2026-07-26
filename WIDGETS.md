# LinkBoard Widgets

[Back to README](README.md)

LinkBoard ships with **136 built-in widgets** that display real-time data from self-hosted services. Each widget is configured per-service in the service editor. The table below lists every available widget, its internal ID, and the configuration fields it accepts.

## How to configure a widget

Widgets are configured **in the LinkBoard service editor** (the dialog you open when adding or editing a service tile) — there is no YAML or config file. Pick a widget type from the dropdown, then fill in the fields the widget needs. The fields available for each widget are listed in the table below.

### Service URL

The widget always uses the same URL as the service tile itself. Make sure the protocol matches what the target service expects — most modern services (Proxmox, TrueNAS, UniFi OS, Synology…) require `https://`.

### Credentials and tokens

Most widgets accept a plain value: either a `username` + `password` pair, or a single `api_key` / `token` that you paste in as-is.

A few widgets need a **composite token** assembled from several pieces. The two Proxmox widgets are the most common example:

| Widget | Token format | Example |
|---|---|---|
| Proxmox VE | `<user>@<realm>!<token_id>=<secret>` | `monitor@pam!linkboard=12345678-1234-1234-1234-123456789abc` |
| Proxmox Backup Server | `<user>@<realm>!<token_id>:<secret>` | `monitor@pbs!linkboard:12345678-1234-1234-1234-123456789abc` |

Notes for both Proxmox widgets:

- `<user>` is the Proxmox user name, `<realm>` is the authentication realm (typically `pam` or `pve` for VE, `pbs` for Backup Server).
- `<token_id>` is the **ID** of the API token (you choose this when creating the token).
- `<secret>` is the **UUID** Proxmox shows you exactly once when the token is created — copy it immediately.
- Proxmox VE uses `=` between token_id and secret; Proxmox Backup Server uses `:`. They are not interchangeable.
- The token needs a role with read permissions: `PVEAuditor` on `/` for Proxmox VE, `Audit` on `/` (with Propagate) for Proxmox Backup Server.

Notes for the Fritz!Box widget:

- Enter the box's normal address as the service URL — `http://192.168.178.1` or `http://fritz.box`. The widget appends **port 49000** itself, because that is where every FRITZ!Box serves its TR-064/IGD interface; the web interface port has no such endpoint. Naming a port explicitly overrides this.
- Enable **Home Network → Network → Network Settings → "Transmit status information over UPnP"** on the box. Without it the interface answers HTTP 401.
- **A connection over PPPoE needs credentials.** UPnP splits the WAN across two services and a box fills in only the one matching its connection type: cable and other IP-routed lines report on `WANIPConnection`, which is public, while PPPoE lines report on `WANPPPConnection`, which FRITZ!OS serves on its TR-064 interface behind HTTP digest authentication. A box that reports the WAN status `Unconfigured` while its internet connection plainly works is telling you exactly this. Enable **"Allow access for applications"** in the same box dialog and enter a FRITZ!Box user plus password that may read the box settings.
- **Otherwise no credentials are required** — the four values an IP-routed box reports are public on the UPnP interface.
- The **Nextcloud server** must be allowed to open outgoing connections to tcp/49000 on the box. A firewall that drops them produces `cURL 28` (timeout) on the tile, not a refusal — the widget is fine, the packets never arrive.
- A box that runs no internet connection of its own — IP client mode, bridge mode, or cascaded behind another router — reports the WAN status `Unconfigured` on both interfaces and an uptime of `0`. All four values then stay at `—` and the tile names the reason underneath. That is the box's own answer, not a widget failure.
- The **uptime is the connection uptime**, not the box's system uptime — the same "connected since" figure the box shows in its own UI. On PPPoE lines it resets whenever the provider renews the connection.

Notes for the Immich widget:

- The API key must belong to an **administrator account**. Immich serves the statistics endpoint the widget reads (`/api/server/statistics`) to admins only — an API key created on a regular account returns HTTP 403 even when every permission box is ticked.
- The only permission the key needs is **`server.statistics`**. Everything else can be left unchecked.
- Requires **Immich 1.118 or newer**. Older releases expose the statistics under `/api/server-info/…` and answer with HTTP 404.
- The service URL is the plain Immich base URL — no `/api` suffix and no sub-path.

## Troubleshooting

If a widget doesn't respond as expected, double-check the protocol (`https://` vs `http://`), the token format, and the role/permissions on the token in the upstream service.

When a widget fails, the tile shows the reason the upstream service gave:

| Message | Meaning |
|---|---|
| `HTTP 401` / `HTTP 403` | The credential is wrong, lacks a required permission, or a reverse proxy in front of the service (Authelia, Authentik, Cloudflare Access…) demands its own login. |
| `HTTP 404` | Wrong base URL — a sub-path or a trailing `/api` in the service URL — or an upstream version whose API paths have since changed. |
| `HTTP 5xx` | The upstream service itself errored. |
| `cURL <n>` | Network or TLS level failure before any response arrived, e.g. `7` connection refused, `28` timeout, `60` certificate not trusted. See the [libcurl error codes](https://curl.se/libcurl/c/libcurl-errors.html). |

A green status dot does **not** rule out an authentication problem: the status check treats every response between HTTP 200 and 499 as online, so a service answering 403 still shows as reachable. The status check also uses the optional ping URL when one is set, while widgets always use the service URL itself.

## Widget catalog

| Widget | ID | Configuration | Tested |
|---|---|---|---|
| AdGuard Home | `adguard` | `username` (required), `password` (required) | [x] |
| APC UPS | `apcups` | — | [ ] |
| Arcane | `arcane` | `api_key` (required), `environment_id` (optional) | [x] |
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
| Fritz!Box | `fritzbox` | `username` (optional), `password` (optional) | [x] |
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
| Pi-hole | `pihole` | `password` (required, Pi-hole v6+) | [x] |
| Plant-It | `plantit` | `api_key` (required) | [ ] |
| Plex | `plex` | `token` (required) | [ ] |
| Portainer | `portainer` | `api_key` (required), `env` (optional) | [ ] |
| Prometheus | `prometheus` | `api_key` (optional) | [ ] |
| Prowlarr | `prowlarr` | `api_key` (required) | [ ] |
| Proxmox Backup Server | `proxmoxbackupserver` | `api_token` (required) — Format: `<user>@<realm>!<token_id>:<secret>` (see [composite tokens](#credentials-and-tokens) for a full example). Token needs `Audit` role on path `/` with Propagate. Service URL must use `https://`. | [x] |
| Proxmox VE | `proxmox` | `api_token` (required) — Format: `<user>@<realm>!<token_id>=<secret>` (see [composite tokens](#credentials-and-tokens) for a full example). Token needs `PVEAuditor` role on path `/`. Service URL must use `https://`. | [x] |
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
| Table | `table` | — | [x] |
| Tailscale | `tailscale` | `api_key` (required), `tailnet` (required) | [ ] |
| Tandoor | `tandoor` | `api_key` (required) | [ ] |
| Tautulli | `tautulli` | `api_key` (required) | [ ] |
| Tdarr | `tdarr` | — | [ ] |
| Technitium DNS | `technitiumdns` | `api_key` (required) | [ ] |
| Traefik | `traefik` | `username` (optional), `password` (optional) | [ ] |
| Transmission | `transmission` | `username` (optional), `password` (optional) | [ ] |
| Trilium | `trilium` | `api_key` (required) — shows version, DB version, note count | [x] |
| TrueNAS | `truenas` | `api_key` (required) — Uses WebSocket JSON-RPC (`/api/current`) for TrueNAS v25.04+ compatibility | [x] |
| TubeArchivist | `tubearchivist` | `api_key` (required) | [ ] |
| UniFi Controller | `unifi` | `controllerType` (select: `UniFi OS (UDM, Cloud Key Gen2+)` / `Legacy Controller`, default UniFi OS), `username` (required), `password` (required), `site` (optional). **Note:** `unifi.ui.com` is not supported — use your local controller IP. | [x] |
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
