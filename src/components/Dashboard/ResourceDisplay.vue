<!--
LinkBoard - ResourceDisplay.vue
Displays system resource metrics with progress bars

SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
    <div class="resource-display">
        <!-- CPU -->
        <div v-if="config.showCpu && data && data.cpu" class="resource-display__item">
            <div class="resource-display__row">
                <ChipIcon :size="18" class="resource-display__icon" :style="manualColors.widgetLabel ? { color: manualColors.widgetLabel } : {}" />
                <span class="resource-display__label" :style="manualColors.widgetValue ? { color: manualColors.widgetValue } : {}">{{ t('linkboard', 'CPU') }}</span>
                <span class="resource-display__value" :style="manualColors.widgetLabel ? { color: manualColors.widgetLabel } : {}">{{ data.cpu.percent }}%</span>
            </div>
            <div class="resource-display__bar">
                <div class="resource-display__bar-fill" :style="barStyle(data.cpu.percent)"></div>
            </div>
        </div>

        <!-- Memory -->
        <div v-if="config.showMemory && data && data.memory" class="resource-display__item">
            <div class="resource-display__row">
                <MemoryIcon :size="18" class="resource-display__icon" :style="manualColors.widgetLabel ? { color: manualColors.widgetLabel } : {}" />
                <span class="resource-display__label" :style="manualColors.widgetValue ? { color: manualColors.widgetValue } : {}">{{ t('linkboard', 'Memory') }}</span>
                <span v-if="data.memory.total" class="resource-display__value" :style="manualColors.widgetLabel ? { color: manualColors.widgetLabel } : {}">{{ formatBytes(data.memory.used) }} {{ t('linkboard', 'of {total}', { total: formatBytes(data.memory.total) }) }}</span>
                <span v-else class="resource-display__value" :style="manualColors.widgetLabel ? { color: manualColors.widgetLabel } : {}">{{ formatBytes(data.memory.used) }}</span>
            </div>
            <div v-if="data.memory.percent !== null" class="resource-display__bar">
                <div class="resource-display__bar-fill" :style="barStyle(data.memory.percent)"></div>
            </div>
        </div>

        <!-- Disks -->
        <div v-for="(disk, idx) in (data && data.disks || [])" :key="'disk-' + idx" class="resource-display__item">
            <div class="resource-display__row">
                <HarddiskIcon :size="18" class="resource-display__icon" :style="manualColors.widgetLabel ? { color: manualColors.widgetLabel } : {}" />
                <span class="resource-display__label" :style="manualColors.widgetValue ? { color: manualColors.widgetValue } : {}">{{ disk.path }}</span>
                <span class="resource-display__value" :style="manualColors.widgetLabel ? { color: manualColors.widgetLabel } : {}">{{ formatBytes(disk.used) }} {{ t('linkboard', 'of {total}', { total: formatBytes(disk.total) }) }}</span>
            </div>
            <div class="resource-display__bar">
                <div class="resource-display__bar-fill" :style="barStyle(disk.percent)"></div>
            </div>
        </div>

        <!-- CPU Temperature -->
        <div v-if="config.showCpuTemp && data && data.cpuTemp" class="resource-display__item">
            <div class="resource-display__row">
                <ThermometerIcon :size="18" class="resource-display__icon" :style="manualColors.widgetLabel ? { color: manualColors.widgetLabel } : {}" />
                <span class="resource-display__label" :style="manualColors.widgetValue ? { color: manualColors.widgetValue } : {}">{{ t('linkboard', 'CPU') }} Temp</span>
                <span class="resource-display__value" :style="manualColors.widgetLabel ? { color: manualColors.widgetLabel } : {}">{{ data.cpuTemp.value }}&deg;{{ data.cpuTemp.unit }}</span>
            </div>
        </div>

        <!-- Uptime -->
        <div v-if="config.showUptime && data && data.uptime" class="resource-display__item">
            <div class="resource-display__row">
                <ClockOutlineIcon :size="18" class="resource-display__icon" :style="manualColors.widgetLabel ? { color: manualColors.widgetLabel } : {}" />
                <span class="resource-display__label" :style="manualColors.widgetValue ? { color: manualColors.widgetValue } : {}">{{ t('linkboard', 'Uptime') }}</span>
                <span class="resource-display__value" :style="manualColors.widgetLabel ? { color: manualColors.widgetLabel } : {}">{{ data.uptime }}</span>
            </div>
        </div>

        <!-- No data -->
        <div v-if="!data" class="resource-display__empty">
            <NcLoadingIcon :size="20" />
        </div>
    </div>
</template>

<script>
import { t } from '@nextcloud/l10n'
import { NcLoadingIcon } from '@nextcloud/vue'
import ChipIcon from 'vue-material-design-icons/Chip.vue'
import MemoryIcon from 'vue-material-design-icons/Memory.vue'
import HarddiskIcon from 'vue-material-design-icons/Harddisk.vue'
import ThermometerIcon from 'vue-material-design-icons/Thermometer.vue'
import ClockOutlineIcon from 'vue-material-design-icons/ClockOutline.vue'

export default {
    name: 'ResourceDisplay',

    components: {
        NcLoadingIcon,
        ChipIcon, MemoryIcon, HarddiskIcon, ThermometerIcon, ClockOutlineIcon,
    },

    props: {
        data: { type: Object, default: null },
        config: { type: Object, default: function() { return {} } },
        manualColors: { type: Object, default: function() { return {} } },
    },

    methods: {
        t,

        barStyle: function(percent) {
            var color = 'var(--color-main-text)'
            if (percent >= 85) {
                color = 'var(--color-error)'
            } else if (percent >= 60) {
                color = 'var(--color-warning)'
            }
            return {
                width: percent + '%',
                backgroundColor: color,
            }
        },

        formatBytes: function(bytes) {
            if (bytes === 0 || bytes === null || bytes === undefined) return '0 B'
            var units = ['B', 'KiB', 'MiB', 'GiB', 'TiB']
            var i = 0
            var val = bytes
            while (val >= 1024 && i < units.length - 1) {
                val /= 1024
                i++
            }
            return val.toFixed(i > 0 ? 1 : 0) + ' ' + units[i]
        },
    },
}
</script>

<style lang="scss" scoped>
.resource-display {
    display: flex;
    flex-direction: column;
    gap: 12px;
    padding: 8px 0;

    &__item {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    &__row {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    &__icon {
        color: var(--color-text-maxcontrast);
        flex-shrink: 0;
    }

    &__label {
        font-size: 13px;
        font-weight: 500;
        color: var(--color-main-text);
    }

    &__value {
        margin-left: auto;
        font-size: 12px;
        color: var(--color-text-maxcontrast);
        white-space: nowrap;
    }

    &__bar {
        height: 6px;
        background: var(--color-background-dark);
        border-radius: 3px;
        overflow: hidden;
    }

    &__bar-fill {
        height: 100%;
        border-radius: 3px;
        transition: width 0.5s ease, background-color 0.3s ease;
    }

    &__empty {
        display: flex;
        justify-content: center;
        padding: 16px;
    }
}
</style>
