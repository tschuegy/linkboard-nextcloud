/**
 * LinkBoard - Spacer style definitions
 * Shared between SettingsPage and CategoryGroup
 *
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

export var SPACER_CHARS = {
	thin: '\u2500',
	heavy: '\u2501',
	'double-line': '\u2550',
	'light-dashed': '\u254C',
	'heavy-dashed': '\u254D',
	dots: '\u00B7',
	wave: '\u3030',
	stars: '\u2726',
	diamonds: '\u25C6',
	fade: '\u2591\u2592\u2593\u2588\u2588\u2593\u2592\u2591',
	arrows: '\u25B8',
}

export var SPACER_STYLES = [
	{ id: 'solid', type: 'css' },
	{ id: 'dashed', type: 'css' },
	{ id: 'dotted', type: 'css' },
	{ id: 'double', type: 'css' },
	{ id: 'thin', type: 'unicode' },
	{ id: 'heavy', type: 'unicode' },
	{ id: 'double-line', type: 'unicode' },
	{ id: 'light-dashed', type: 'unicode' },
	{ id: 'heavy-dashed', type: 'unicode' },
	{ id: 'dots', type: 'unicode' },
	{ id: 'wave', type: 'unicode' },
	{ id: 'stars', type: 'unicode' },
	{ id: 'diamonds', type: 'unicode' },
	{ id: 'fade', type: 'unicode' },
	{ id: 'arrows', type: 'unicode' },
]

export var SPACER_LABELS = {
	solid: 'Solid',
	dashed: 'Dashed',
	dotted: 'Dotted',
	double: 'Double',
	thin: 'Thin',
	heavy: 'Heavy',
	'double-line': 'Double Line',
	'light-dashed': 'Light Dashed',
	'heavy-dashed': 'Heavy Dashed',
	dots: 'Dots',
	wave: 'Wave',
	stars: 'Stars',
	diamonds: 'Diamonds',
	fade: 'Fade',
	arrows: 'Arrows',
}

export function isUnicodeStyle(id) {
	return !!SPACER_CHARS[id]
}

export function getSpacerChar(id) {
	return SPACER_CHARS[id] || ''
}
