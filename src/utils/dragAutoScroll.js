/**
 * LinkBoard - Auto-scroll the dashboard while dragging
 *
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/*
 * Why this exists instead of SortableJS' own auto-scroll (issue #16):
 *
 * The AutoScroll plugin listens for `dragover` on `document` in the bubble
 * phase, but Sortable stops that event's propagation inside `_onDragOver`
 * (`dragoverBubble` defaults to false). While the cursor is over one of our
 * lists — which is exactly the reported case, a list taller than the screen —
 * the plugin's handler is therefore never reached and nothing scrolls.
 *
 * A capture-phase listener sees the event regardless, so we drive the scroll
 * ourselves and switch the plugin off (`scroll: false`) to keep a single
 * mechanism in play.
 */

// Distance from the visible edge at which scrolling starts, and the step per
// frame right at the edge (it ramps up across the band).
var EDGE = 90
var MAX_SPEED = 16

var running = false
var pointerY = null
var frame = null

function loop() {
	frame = null
	if (!running) return
	// Resolved per frame: the container is the app's only scroller (App.vue),
	// but it is not ours to hold on to across re-renders.
	var el = document.querySelector('.linkboard-content')
	if (el && pointerY !== null) {
		var rect = el.getBoundingClientRect()
		// Intersect with the viewport — the cursor can only ever reach the
		// visible part of the container, so that is where the band belongs.
		var top = Math.max(rect.top, 0)
		var bottom = Math.min(rect.bottom, window.innerHeight)
		var speed = 0
		if (pointerY > bottom - EDGE) {
			speed = MAX_SPEED * Math.min(1, (pointerY - (bottom - EDGE)) / EDGE)
		} else if (pointerY < top + EDGE) {
			speed = -MAX_SPEED * Math.min(1, (top + EDGE - pointerY) / EDGE)
		}
		if (speed) el.scrollTop += speed
	}
	frame = requestAnimationFrame(loop)
}

function onDragOver(evt) {
	pointerY = evt.clientY
}

function start() {
	if (running) return
	running = true
	// Unknown until the first dragover; without this the loop would act on the
	// previous drag's position.
	pointerY = null
	document.addEventListener('dragover', onDragOver, true)
	document.addEventListener('dragend', stop, true)
	frame = requestAnimationFrame(loop)
}

function stop() {
	if (!running) return
	running = false
	pointerY = null
	document.removeEventListener('dragover', onDragOver, true)
	document.removeEventListener('dragend', stop, true)
	if (frame) {
		cancelAnimationFrame(frame)
		frame = null
	}
}

/**
 * Wrap Sortable options so the dashboard scrolls while this Sortable drags.
 *
 * Existing `onStart` / `onEnd` handlers are kept and still receive their
 * arguments; `onEnd` fires on the source list for every outcome, including a
 * drag cancelled with Escape, and a `dragend` listener backs that up.
 *
 * @param {object} options Sortable options; mutated and returned.
 * @return {object} The same options object.
 */
export function withAutoScroll(options) {
	var userStart = options.onStart
	var userEnd = options.onEnd
	options.scroll = false
	options.onStart = function() {
		start()
		if (userStart) userStart.apply(this, arguments)
	}
	options.onEnd = function() {
		stop()
		if (userEnd) userEnd.apply(this, arguments)
	}
	return options
}
