/**
 * Grid layout migration utility.
 * Converts legacy _colSpan data to _layout objects for vue-grid-layout.
 */

var DEFAULT_COL_COUNT = 12
var DEFAULT_ROW_HEIGHT = 80
var DEFAULT_H = 2
var DEFAULT_W_NO_WIDGET = 3
var DEFAULT_W_WIDGET = 6
var DEFAULT_H_WIDGET = 3

/**
 * Map old category columns (1-6) to new colCount (6, 12, 24).
 */
function mapColumnsToColCount(columns) {
	if (!columns || columns <= 2) return 6
	if (columns <= 4) return 12
	return 24
}

/**
 * Get default grid settings for a category.
 */
function getDefaultGridSettings() {
	return {
		colCount: DEFAULT_COL_COUNT,
		rowHeight: DEFAULT_ROW_HEIGHT,
		autoCompress: true,
		minHeight: 0,
	}
}

/**
 * Migrate a category's columns field to _gridSettings.
 * Returns the new config object, or null if no migration needed.
 */
function migrateCategory(category) {
	var config = category.config || {}
	if (typeof config === 'string') {
		try { config = JSON.parse(config) } catch (e) { config = {} }
	}
	if (config._gridSettings) return null // already migrated

	var gridSettings = getDefaultGridSettings()
	if (category.columns) {
		gridSettings.colCount = mapColumnsToColCount(category.columns)
	}
	config._gridSettings = gridSettings
	return config
}

/**
 * Determine the effective width for a service being migrated.
 */
function getEffectiveWidth(service, colCount, effectiveOldColumns) {
	var cfg = service.widgetConfig || {}
	var colSpan = cfg._colSpan ? parseInt(cfg._colSpan) : null

	if (colSpan) {
		return Math.max(1, Math.min(colCount, Math.round(colSpan * (colCount / effectiveOldColumns))))
	}
	if (service.widgetType === 'resources' || cfg._wide) {
		return Math.round(2 * (colCount / effectiveOldColumns))
	}
	if (service.widgetType) {
		return DEFAULT_W_WIDGET
	}
	return DEFAULT_W_NO_WIDGET
}

/**
 * Migrate services within a category from _colSpan to _layout.
 * Uses a packing algorithm to assign x, y positions sequentially.
 * Returns array of { serviceId, widgetConfig } for services that need updating, or empty array.
 */
function migrateServices(services, colCount, effectiveOldColumns) {
	var updates = []
	var currentX = 0
	var currentY = 0
	var rowMaxH = 0

	for (var i = 0; i < services.length; i++) {
		var service = services[i]
		var cfg = service.widgetConfig || {}
		if (typeof cfg === 'string') {
			try { cfg = JSON.parse(cfg) } catch (e) { cfg = {} }
		}

		// Skip if already migrated
		if (cfg._layout) continue

		// Only migrate if there's something to migrate (_colSpan, _wide, or just assign defaults)
		var w = getEffectiveWidth(service, colCount, effectiveOldColumns)
		var h = service.widgetType ? DEFAULT_H_WIDGET : DEFAULT_H

		// Pack into grid
		if (currentX + w > colCount) {
			currentX = 0
			currentY += rowMaxH
			rowMaxH = 0
		}

		var newCfg = Object.assign({}, cfg)
		newCfg._layout = { x: currentX, y: currentY, w: w, h: h }
		delete newCfg._colSpan
		delete newCfg._wide

		updates.push({
			serviceId: service.id,
			widgetConfig: newCfg,
		})

		currentX += w
		rowMaxH = Math.max(rowMaxH, h)
	}

	return updates
}

/**
 * Assign a default layout position for a new service.
 * Finds the first available position after existing services.
 */
function assignDefaultLayout(existingServices, hasWidget, colCount) {
	colCount = colCount || DEFAULT_COL_COUNT
	var w = hasWidget ? DEFAULT_W_WIDGET : DEFAULT_W_NO_WIDGET
	var h = hasWidget ? DEFAULT_H_WIDGET : DEFAULT_H

	// Find max y + h from existing layouts
	var maxBottom = 0
	for (var i = 0; i < existingServices.length; i++) {
		var layout = (existingServices[i].widgetConfig || {})._layout
		if (layout) {
			var bottom = layout.y + layout.h
			if (bottom > maxBottom) maxBottom = bottom
		}
	}

	return { x: 0, y: maxBottom, w: w, h: h }
}

export {
	mapColumnsToColCount,
	getDefaultGridSettings,
	migrateCategory,
	migrateServices,
	assignDefaultLayout,
	DEFAULT_COL_COUNT,
	DEFAULT_ROW_HEIGHT,
}
