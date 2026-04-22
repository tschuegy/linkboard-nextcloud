<!--
LinkBoard - App.vue (Root Component)

SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
    <NcContent app-name="linkboard">
        <NcAppContent class="linkboard-content">
            <router-view />
        </NcAppContent>
    </NcContent>
</template>

<script>
import { NcContent, NcAppContent } from '@nextcloud/vue'

export default {
    name: 'App',
    components: {
        NcContent,
        NcAppContent,
    },
    created() {
        this.scrollTarget = null
        this.scrollRaf = null
    },
    mounted() {
        window.addEventListener('wheel', this.handleWheel, { passive: false })
    },
    beforeDestroy() {
        window.removeEventListener('wheel', this.handleWheel)
        if (this.scrollRaf) {
            cancelAnimationFrame(this.scrollRaf)
        }
    },
    methods: {
        // Scroll .linkboard-content from anywhere on the page — but only take over
        // when the native wheel scroll wouldn't work (viewport edges outside the
        // container, or a fixed-position overlay blocking it). Otherwise stay out
        // of the way so the browser's smooth scrolling runs.
        handleWheel(e) {
            var container = document.querySelector('.linkboard-content')
            if (!container) return

            if (container.contains(e.target)) {
                var node = e.target
                var blockedByFixed = false
                while (node && node !== container && node.nodeType === 1) {
                    var style = window.getComputedStyle(node)
                    var overflowY = style.overflowY
                    if ((overflowY === 'auto' || overflowY === 'scroll')
                        && node.scrollHeight > node.clientHeight) {
                        var atTop = node.scrollTop <= 0
                        var atBottom = node.scrollTop + node.clientHeight >= node.scrollHeight
                        if ((e.deltaY > 0 && !atBottom) || (e.deltaY < 0 && !atTop)) {
                            this.cancelScrollAnimation()
                            return
                        }
                    }
                    if (style.position === 'fixed') {
                        blockedByFixed = true
                        break
                    }
                    node = node.parentElement
                }
                if (!blockedByFixed) {
                    // Native scroll will handle this. Drop any in-flight programmatic
                    // animation so it doesn't fight the native scroll (otherwise it
                    // would pull back to a stale target).
                    this.cancelScrollAnimation()
                    return
                }
            }

            e.preventDefault()
            this.smoothScrollBy(container, e.deltaY)
        },

        cancelScrollAnimation() {
            if (this.scrollRaf) {
                cancelAnimationFrame(this.scrollRaf)
                this.scrollRaf = null
            }
            this.scrollTarget = null
        },

        // Eased, animated scroll — accumulates wheel deltas into a target position
        // and approaches it via requestAnimationFrame, matching the feel of native
        // smooth scrolling when we have to forward events programmatically.
        smoothScrollBy(container, deltaY) {
            var maxScroll = container.scrollHeight - container.clientHeight
            var baseline = this.scrollTarget !== null ? this.scrollTarget : container.scrollTop
            this.scrollTarget = Math.max(0, Math.min(maxScroll, baseline + deltaY))
            if (this.scrollRaf) return
            var self = this
            var lastWritten = null
            var step = function () {
                if (self.scrollTarget === null) {
                    self.scrollRaf = null
                    return
                }
                var current = container.scrollTop
                // If something external (native scroll) moved the position between
                // frames, abandon our target — the user's latest input wins.
                if (lastWritten !== null && Math.abs(current - lastWritten) > 2) {
                    self.scrollTarget = null
                    self.scrollRaf = null
                    return
                }
                var diff = self.scrollTarget - current
                if (Math.abs(diff) < 0.5) {
                    container.scrollTop = self.scrollTarget
                    self.scrollTarget = null
                    self.scrollRaf = null
                    return
                }
                container.scrollTop = current + diff * 0.2
                lastWritten = container.scrollTop
                self.scrollRaf = requestAnimationFrame(step)
            }
            self.scrollRaf = requestAnimationFrame(step)
        },
    },
}
</script>

<style lang="scss">
// Fix scrolling - NC app content needs explicit overflow
.linkboard-content {
    overflow-y: auto !important;
    padding-bottom: 200px;
}
</style>
