<!--
LinkBoard - ResponseTimeChart.vue
Reusable response time chart SVG component

SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
    <div v-if="chartPoints.length > 1" class="response-chart__section">
        <h4>{{ t('linkboard', 'Response time') }} (ms)</h4>
        <div class="response-chart__wrap">
            <div
                class="response-chart__container"
                @mousemove="onChartHover"
                @mouseleave="onChartLeave">
                <svg :viewBox="'0 0 ' + chartWidth + ' ' + chartHeight" preserveAspectRatio="none">
                    <!-- Horizontal grid lines with ms labels -->
                    <template v-for="(line, i) in gridLines">
                        <line
                            :key="'grid-' + i"
                            :x1="0"
                            :y1="line.y"
                            :x2="chartWidth"
                            :y2="line.y"
                            stroke="rgba(255, 255, 255, 0.15)"
                            stroke-width="0.5"
                            vector-effect="non-scaling-stroke" />
                        <text
                            :key="'label-' + i"
                            :x="4"
                            :y="line.y - 3"
                            fill="var(--color-text-maxcontrast)"
                            font-size="9"
                            vector-effect="non-scaling-stroke">
                            {{ line.label }}
                        </text>
                    </template>
                    <!-- Offline regions -->
                    <rect
                        v-for="(region, i) in offlineRegions"
                        :key="'off-' + i"
                        :x="region.x"
                        :y="0"
                        :width="region.width"
                        :height="chartHeight"
                        fill="rgba(239, 68, 68, 0.1)" />
                    <!-- Response time line -->
                    <polyline
                        :points="polylinePoints"
                        fill="none"
                        stroke="#22c55e"
                        stroke-width="1.5"
                        vector-effect="non-scaling-stroke" />
                </svg>
                <!-- Hover indicators -->
                <div
                    v-if="hoveredPoint"
                    class="response-chart__hover-line"
                    :style="{ left: hoverX + 'px' }" />
                <div
                    v-if="hoveredPoint"
                    class="response-chart__dot"
                    :style="{ left: (hoverX - 3) + 'px', top: (hoverY - 3) + 'px' }" />
                <div
                    v-if="hoveredPoint"
                    class="response-chart__tooltip"
                    :style="{ left: hoverX + 'px', top: (hoverY - 32) + 'px' }">
                    {{ hoveredPoint.ms }} ms · {{ formatTime(hoveredPoint.checkedAt) }}
                </div>
            </div>
            <div class="response-chart__labels">
                <span>{{ chartStartLabel }}</span>
                <span>{{ chartEndLabel }}</span>
            </div>
        </div>
    </div>
</template>

<script>
import { t } from '@nextcloud/l10n'

export default {
    name: 'ResponseTimeChart',
    props: {
        historyData: { type: Object, default: null },
        chartWidth: { type: Number, default: 600 },
        chartHeight: { type: Number, default: 120 },
    },
    data: function() {
        return {
            hoverIndex: null,
            hoverX: 0,
            hoverY: 0,
        }
    },
    computed: {
        chartPoints: function() {
            if (!this.historyData || !this.historyData.history) return []
            var points = []
            for (var i = 0; i < this.historyData.history.length; i++) {
                var entry = this.historyData.history[i]
                if (entry.responseMs !== null) {
                    points.push({ index: i, ms: entry.responseMs, status: entry.status, checkedAt: entry.checkedAt })
                }
            }
            return points
        },
        maxMs: function() {
            var max = 0
            for (var i = 0; i < this.chartPoints.length; i++) {
                if (this.chartPoints[i].ms > max) max = this.chartPoints[i].ms
            }
            return max || 1
        },
        polylinePoints: function() {
            if (this.chartPoints.length < 2) return ''
            var total = this.historyData.history.length
            var parts = []
            for (var j = 0; j < this.chartPoints.length; j++) {
                var x = (this.chartPoints[j].index / (total - 1)) * this.chartWidth
                var y = this.chartHeight - (this.chartPoints[j].ms / this.maxMs) * (this.chartHeight - 10) - 5
                parts.push(x.toFixed(1) + ',' + y.toFixed(1))
            }
            return parts.join(' ')
        },
        gridLines: function() {
            if (this.chartPoints.length < 2) return []
            var lines = []
            var lineCount = 3
            for (var i = 1; i <= lineCount; i++) {
                var msVal = Math.round((this.maxMs / (lineCount + 1)) * i)
                var y = this.chartHeight - (msVal / this.maxMs) * (this.chartHeight - 10) - 5
                lines.push({ y: y, label: msVal + ' ms' })
            }
            return lines
        },
        offlineRegions: function() {
            if (!this.historyData || !this.historyData.history) return []
            var regions = []
            var history = this.historyData.history
            var total = history.length
            if (total < 2) return []
            var stepWidth = this.chartWidth / (total - 1)
            var inRegion = false
            var startX = 0
            for (var i = 0; i < total; i++) {
                if (history[i].status === 'offline') {
                    if (!inRegion) {
                        startX = i * stepWidth
                        inRegion = true
                    }
                } else {
                    if (inRegion) {
                        regions.push({ x: startX, width: (i * stepWidth) - startX })
                        inRegion = false
                    }
                }
            }
            if (inRegion) {
                regions.push({ x: startX, width: this.chartWidth - startX })
            }
            return regions
        },
        chartStartLabel: function() {
            if (!this.historyData || !this.historyData.history || this.historyData.history.length === 0) return ''
            return this.formatTime(this.historyData.history[0].checkedAt)
        },
        chartEndLabel: function() {
            if (!this.historyData || !this.historyData.history || this.historyData.history.length === 0) return ''
            return this.formatTime(this.historyData.history[this.historyData.history.length - 1].checkedAt)
        },
        hoveredPoint: function() {
            if (this.hoverIndex === null) return null
            return this.chartPoints[this.hoverIndex] || null
        },
    },
    methods: {
        t,
        formatTime: function(dateStr) {
            if (!dateStr) return ''
            var d = new Date(dateStr.replace(' ', 'T'))
            return d.toLocaleString(undefined, { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })
        },
        onChartHover: function(event) {
            var container = event.currentTarget
            var rect = container.getBoundingClientRect()
            var mouseX = event.clientX - rect.left
            var ratio = mouseX / rect.width
            var total = this.historyData.history.length
            var bestIdx = 0
            var bestDist = Infinity
            for (var i = 0; i < this.chartPoints.length; i++) {
                var pointRatio = this.chartPoints[i].index / (total - 1)
                var dist = Math.abs(pointRatio - ratio)
                if (dist < bestDist) {
                    bestDist = dist
                    bestIdx = i
                }
            }
            this.hoverIndex = bestIdx
            var pt = this.chartPoints[bestIdx]
            this.hoverX = (pt.index / (total - 1)) * rect.width
            this.hoverY = rect.height - (pt.ms / this.maxMs) * (rect.height - (10 * rect.height / this.chartHeight)) - (5 * rect.height / this.chartHeight)
        },
        onChartLeave: function() {
            this.hoverIndex = null
        },
    },
}
</script>

<style lang="scss" scoped>
.response-chart {
    &__section {
        margin-bottom: 16px;

        h4 {
            font-size: 13px;
            font-weight: 600;
            color: var(--color-text-maxcontrast);
            margin: 0 0 8px;
        }
    }

    &__wrap {
        background: var(--color-background-dark);
        border-radius: 8px;
        padding: 12px;

        svg {
            width: 100%;
            height: 120px;
        }
    }

    &__container {
        position: relative;
    }

    &__hover-line {
        position: absolute;
        top: 0;
        bottom: 0;
        width: 1px;
        background: rgba(255, 255, 255, 0.3);
        pointer-events: none;
    }

    &__dot {
        position: absolute;
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: #22c55e;
        pointer-events: none;
    }

    &__tooltip {
        position: absolute;
        transform: translateX(-50%);
        background: rgba(0, 0, 0, 0.85);
        color: #fff;
        font-size: 11px;
        padding: 3px 8px;
        border-radius: 4px;
        white-space: nowrap;
        pointer-events: none;
    }

    &__labels {
        display: flex;
        justify-content: space-between;
        font-size: 11px;
        color: var(--color-text-maxcontrast);
        margin-top: 4px;
    }
}
</style>
