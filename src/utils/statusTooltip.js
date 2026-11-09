/**
 * LinkBoard - statusTooltip.js
 * Builds the status-dot tooltip, including the reason when a check failed.
 *
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { t } from '@nextcloud/l10n'

/**
 * Human-readable reason for a failed status check.
 *
 * @param {object|null} details Status details from the backend ({ error, errno, errorType })
 * @param {boolean} tlsVerificationEnabled Whether the admin enforces TLS verification globally
 * @return {string} Reason text, or an empty string if nothing is known
 */
export function statusErrorReason(details, tlsVerificationEnabled) {
	if (!details) return ''
	switch (details.errorType) {
	case 'tls_certificate':
		return t('linkboard', 'TLS certificate could not be verified (self-signed or expired?)')
			+ '\n' + (tlsVerificationEnabled
			? t('linkboard', 'An administrator can allow per-service exceptions in the LinkBoard admin settings.')
			: t('linkboard', 'Enable "Ignore TLS certificate errors for this service" in the service settings.'))
	case 'tls_handshake':
		return t('linkboard', 'TLS handshake failed')
	case 'timeout':
		return t('linkboard', 'Connection timed out')
	case 'connect':
		return t('linkboard', 'Connection refused or host unreachable')
	case 'dns':
		return t('linkboard', 'Host name could not be resolved')
	}
	return details.error || ''
}

/**
 * Tooltip for the status indicator: "Offline (12ms)" plus the failure reason.
 *
 * @param {object|null} status Status object of a service
 * @param {boolean} tlsVerificationEnabled Whether the admin enforces TLS verification globally
 * @return {string} Tooltip text
 */
export function statusTooltip(status, tlsVerificationEnabled) {
	if (!status) return ''
	var label = status.status === 'online'
		? t('linkboard', 'Online')
		: status.status === 'offline' ? t('linkboard', 'Offline') : t('linkboard', 'Unknown')
	var text = label + (status.responseMs ? ' (' + status.responseMs + 'ms)' : '')
	if (status.status === 'offline') {
		var reason = statusErrorReason(status.details, tlsVerificationEnabled)
		if (reason) text += '\n' + reason
	}
	return text
}
