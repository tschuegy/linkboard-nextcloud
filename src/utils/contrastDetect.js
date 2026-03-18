/**
 * LinkBoard - contrastDetect.js
 * Canvas-based background luminance detection for automatic text color contrast
 *
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/**
 * Detect whether the given background image is dark or light.
 *
 * @param {string} imageUrl URL of the background image to analyze
 * @return {Promise<'light'|'dark'>} 'light' if dark bg (use white text), 'dark' if light bg (use dark text)
 */
export function detectBackgroundLuminance(imageUrl) {
	return new Promise(function(resolve) {
		if (!imageUrl) {
			resolve(detectFromTheme())
			return
		}

		var img = new Image()
		img.crossOrigin = 'anonymous'

		img.onload = function() {
			try {
				var canvas = document.createElement('canvas')
				var size = 50
				canvas.width = size
				canvas.height = size
				var ctx = canvas.getContext('2d')
				ctx.drawImage(img, 0, 0, size, size)
				var imageData = ctx.getImageData(0, 0, size, size)
				var pixels = imageData.data
				var totalR = 0
				var totalG = 0
				var totalB = 0
				var count = pixels.length / 4

				for (var i = 0; i < pixels.length; i += 4) {
					totalR += pixels[i]
					totalG += pixels[i + 1]
					totalB += pixels[i + 2]
				}

				var avgR = totalR / count / 255
				var avgG = totalG / count / 255
				var avgB = totalB / count / 255

				// ITU-R BT.709 relative luminance
				var luminance = 0.2126 * avgR + 0.7152 * avgG + 0.0722 * avgB

				resolve(luminance < 0.5 ? 'light' : 'dark')
			} catch (e) {
				// CORS or canvas tainted — fall back to theme detection
				resolve(detectFromTheme())
			}
		}

		img.onerror = function() {
			resolve(detectFromTheme())
		}

		img.src = imageUrl
	})
}

/**
 * Fallback: detect from Nextcloud dark theme attribute
 *
 * @return {'light'|'dark'} 'light' if NC dark theme detected, else 'dark'
 */
function detectFromTheme() {
	var body = document.body
	var themes = body.getAttribute('data-themes') || ''
	if (themes.indexOf('dark') !== -1) {
		return 'light'
	}
	return 'dark'
}
