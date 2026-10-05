/**
 * Responsive grid layout utility.
 *
 * vue-grid-layout's own responsive mode only clamps the desktop coordinates
 * into the narrower grid and compacts them vertically, which scrambles the
 * card order. Instead, smaller breakpoints get a layout that is rebuilt row
 * by row in the visual order of the stored desktop layout.
 */

// Same thresholds as vue-grid-layout (container width in px)
var BREAKPOINTS = { lg: 1200, md: 996, sm: 768, xs: 480, xxs: 0 }

/**
 * Column count per breakpoint for a category with colCount columns.
 */
export function getResponsiveCols(colCount) {
	var min = Math.max(1, Math.round(colCount * 0.25))
	return {
		lg: colCount,
		md: Math.max(min, Math.round(colCount * 0.67)),
		sm: Math.max(min, Math.round(colCount * 0.5)),
		xs: min,
		xxs: min,
	}
}

/**
 * Breakpoint name for a container width.
 */
export function getBreakpoint(width) {
	if (width > BREAKPOINTS.lg) return 'lg'
	if (width > BREAKPOINTS.md) return 'md'
	if (width > BREAKPOINTS.sm) return 'sm'
	if (width > BREAKPOINTS.xs) return 'xs'
	return 'xxs'
}

/**
 * Re-flow a desktop layout into cols columns: cards keep their visual order
 * (top to bottom, left to right), fill each row from the left and wrap to the
 * next row. Widths are kept, but capped at the available column count.
 */
export function reflowLayout(layout, cols) {
	var ordered = layout.slice().sort(function(a, b) {
		return (a.y - b.y) || (a.x - b.x)
	})
	var result = []
	var x = 0
	var y = 0
	var rowHeight = 0
	for (var i = 0; i < ordered.length; i++) {
		var item = ordered[i]
		var w = Math.max(1, Math.min(item.w, cols))
		if (x > 0 && x + w > cols) {
			x = 0
			y += rowHeight
			rowHeight = 0
		}
		result.push({ i: item.i, x: x, y: y, w: w, h: item.h })
		x += w
		rowHeight = Math.max(rowHeight, item.h)
	}
	return result
}
