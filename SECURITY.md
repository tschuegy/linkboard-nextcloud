# Security Policy

## Supported Versions

| Version | Supported |
|---------|-----------|
| 1.6.x   | Yes       |
| < 1.6   | No        |

## Reporting a Vulnerability

**Please do not report security vulnerabilities through public issues.**

Instead, use [GitHub Private Vulnerability Reporting](https://github.com/tschuegy/linkboard-nextcloud/security/advisories/new) to report security issues.

### What to Include

- Description of the vulnerability
- Steps to reproduce
- Potential impact
- Suggested fix (if any)

### Response Timeline

- **Acknowledgement:** Within 48 hours
- **Assessment:** Within 7 days
- **Fix:** Depending on severity, a patch release will be issued as soon as possible

## Security Considerations

### TLS Verification

TLS certificate verification is enabled by default. Administrators can globally permit per-service exceptions for status checks and widgets that use self-signed certificates in homelab environments. HTTP notification providers and SMTP TLS connections always verify certificates.

### Outbound Requests

User-configured HTTP, WebSocket, and SMTP targets are resolved and validated before connecting. The checked DNS addresses are pinned to the connection and the connected peer is verified again. Loopback, link-local, multicast, documentation, and reserved address ranges are blocked.

Private RFC1918 networks, IPv6 ULA, and CGNAT addresses remain allowed because accessing internal homelab services is a core LinkBoard use case.

### Widget Proxy

The widget proxy makes outbound requests using stored service configurations and returns mapped response values. Request URLs, upstream exception details, and temporary session cookies are not exposed to dashboard viewers.

When a global board is enabled, its widget requests use the designated source user's service configuration. Global-board viewers remain read-only and receive only mapped widget values or generic errors.

### Input Validation and Abuse Resistance

User settings are accepted only through a typed allowlist. Values are normalized and constrained before persistence, bulk updates are validated before the first write, and unsupported settings from imported files are ignored.

Expensive widget and status operations use Nextcloud's rate limiting and atomic cache locks when a locking cache is configured. Widget batches are paginated and time-bounded, individual widgets have a request budget, and successful mapped results are cached briefly to reduce duplicate upstream traffic.

Manual bulk status checks have fixed service and runtime budgets. Periodic background checks remain unrestricted by those manual budgets, while per-service locks prevent overlap with interactive checks. Read-only global-board viewers cannot trigger status checks.
