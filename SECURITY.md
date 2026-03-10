# Security Policy

## Supported Versions

| Version | Supported |
|---------|-----------|
| 1.4.x   | Yes       |
| < 1.4   | No        |

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

### SSL Verification

LinkBoard disables SSL certificate verification by default for service health checks and widget API requests. This is intentional for homelab environments where self-signed certificates are common. Users deploying in production environments with valid certificates should be aware of this behavior.

### Widget Proxy

The widget proxy (`WidgetProxyController`) makes outbound HTTP requests to user-configured service URLs. These requests are scoped to the authenticated user's own service configurations and are not accessible to other users.
