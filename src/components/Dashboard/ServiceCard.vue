<!--
LinkBoard - ServiceCard.vue
Service tile with drag handle and status indicator

SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
    <div
        class="service-card"
        :class="[
            { 'service-card--edit': editMode },
            'service-card--style-' + cardStyle,
            'service-card--bg-' + cardBackground,
            statusClass,
        ]"
        :style="manualColors.cardBg ? { backgroundColor: manualColors.cardBg } : {}"
        @click="$emit('click')">

        <!-- Edit button -->
        <button
            v-if="editMode"
            class="service-card__edit-btn"
            :aria-label="t('linkboard', 'Edit service {name}', { name: service.name })"
            @click.stop="$emit('edit')">
            <PencilIcon :size="16" />
        </button>

        <!-- Status indicator dot -->
        <span
            v-if="service.status && service.pingEnabled"
            class="service-card__status"
            :class="'service-card__status--' + service.status.status"
            :title="statusTooltip"
            @click.stop="$emit('status-click', service)" />

        <div v-if="service.name || service.icon" class="service-card__content">
            <ServiceIcon
                :icon="service.icon"
                :name="service.name"
                :color="service.iconColor"
                :size="iconSize"
                class="service-card__icon" />
            <div class="service-card__info">
                <span class="service-card__name" :style="manualColors.service ? { color: manualColors.service } : {}">{{ service.name }}</span>
                <span v-if="service.description && cardStyle !== 'minimal'" class="service-card__description" :style="manualColors.description ? { color: manualColors.description } : {}">
                    {{ service.description }}
                </span>
            </div>
        </div>
        <ResourceDisplay
            v-if="service.widgetType === 'resources' && widgetData"
            :data="widgetData.data || null"
            :config="resourceConfig"
            :manual-colors="manualColors" />
        <TableDisplay
            v-else-if="service.widgetType === 'table'"
            :service="service"
            :edit-mode="editMode"
            :manual-colors="manualColors" />
        <WidgetContainer
            v-else-if="service.widgetType && widgetData"
            :data="filteredWidgetFields"
            :field-labels="widgetData.fieldLabels || {}"
            :error="widgetData.error || null"
            :warning="widgetWarning"
            :items-per-row="widgetItemsPerRow"
            :manual-colors="manualColors" />

        <!-- Mini status history bars -->
        <div v-if="showStatusBars && hasHistoryBars" class="service-card__history-bars" :style="{ opacity: statusBarsOpacity }">
            <span v-for="(entry, idx) in service.recentHistory"
                :key="idx"
                class="service-card__history-bar"
                :class="'service-card__history-bar--' + entry.status" />
        </div>
    </div>
</template>

<script>
import { t } from '@nextcloud/l10n'
import ServiceIcon from '../Shared/ServiceIcon.vue'
import WidgetContainer from '../Widget/WidgetContainer.vue'
import ResourceDisplay from './ResourceDisplay.vue'
import TableDisplay from './TableDisplay.vue'
import PencilIcon from 'vue-material-design-icons/Pencil.vue'
export default {
    name: 'ServiceCard',
    components: { ServiceIcon, WidgetContainer, ResourceDisplay, TableDisplay, PencilIcon },
    props: {
        service: { type: Object, required: true },
        editMode: { type: Boolean, default: false },
        cardStyle: { type: String, default: 'default' },
        cardBackground: { type: String, default: 'glass' },
        statusStyle: { type: String, default: 'dot' },
        widgetData: { type: Object, default: null },
        showStatusBars: { type: Boolean, default: true },
        statusBarsOpacity: { type: String, default: '0.8' },
        manualColors: { type: Object, default: function() { return {} } },
    },
    methods: {
        t,
    },
    computed: {
        filteredWidgetFields: function() {
            var data = this.widgetData && this.widgetData.data ? this.widgetData.data : {}
            var result = {}
            for (var key in data) {
                if (key.charAt(0) !== '_') {
                    result[key] = data[key]
                }
            }
            return result
        },
        widgetWarning: function() {
            return this.widgetData && this.widgetData.data ? (this.widgetData.data._warning || null) : null
        },
        resourceConfig: function() {
            return { showCpu: true, showMemory: true, showUptime: true }
        },
        iconSize: function() {
            if (this.cardStyle === 'compact') return 28
            if (this.cardStyle === 'minimal') return 24
            return 40
        },
        hasHistoryBars: function() {
            return this.service.pingEnabled && this.service.recentHistory && this.service.recentHistory.length > 0
        },
        statusClass: function() {
            if (this.statusStyle !== 'basic' || !this.service.status || !this.service.pingEnabled) return ''
            return 'service-card--status-' + this.service.status.status
        },
        widgetItemsPerRow: function() {
            var cfg = this.service.widgetConfig
            return cfg && cfg._itemsPerRow ? parseInt(cfg._itemsPerRow) : 0
        },
        statusTooltip: function() {
            var s = this.service.status
            if (!s) return ''
            var status = s.status === 'online' ? t('linkboard', 'Online') : s.status === 'offline' ? t('linkboard', 'Offline') : t('linkboard', 'Unknown')
            var ms = s.responseMs ? ' (' + s.responseMs + 'ms)' : ''
            return status + ms
        },
    },
}
</script>

<style lang="scss" scoped>
.service-card {
    position: relative;
    display: flex;
    flex-direction: column;
    padding: 12px 16px;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.15s ease;
    min-height: 64px;
    height: 100%;
    overflow-y: auto;

    &:hover {
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    }

    &--edit {
        border-style: dashed;
        padding-left: 32px;
        &:hover { border-color: var(--color-primary); }
    }

    &--ghost {
        opacity: 0.4;
        background: var(--color-primary-element-light) !important;
        border: 2px dashed var(--color-primary) !important;
    }

    &__edit-btn {
        position: absolute;
        top: 6px; right: 6px;
        width: 28px; height: 28px;
        display: flex; align-items: center; justify-content: center;
        background: var(--color-background-dark);
        border: 1px solid var(--color-border);
        border-radius: 6px; cursor: pointer;
        opacity: 0; transition: opacity 0.15s;
        color: var(--color-main-text);
        .service-card:hover & { opacity: 1; }
    }

    &__status {
        position: absolute;
        top: 8px; left: 8px;
        width: 8px; height: 8px;
        border-radius: 50%;
        cursor: pointer;
        &--online { background: #22c55e; box-shadow: 0 0 4px rgba(34, 197, 94, 0.6); }
        &--offline { background: #ef4444; box-shadow: 0 0 4px rgba(239, 68, 68, 0.6); }
        &--unknown { background: #a3a3a3; }
    }

    &--edit &__status { left: 34px; }

    // Card background: glass (default)
    &--bg-glass {
        background: rgba(255, 255, 255, 0.12);
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);
        border: 1px solid rgba(255, 255, 255, 0.15);
        &:hover {
            background: rgba(255, 255, 255, 0.18);
            border-color: rgba(255, 255, 255, 0.25);
        }
    }
    [data-themes*="dark"] &--bg-glass {
        background: rgba(255, 255, 255, 0.08);
        border-color: rgba(255, 255, 255, 0.1);
        &:hover {
            background: rgba(255, 255, 255, 0.14);
            border-color: rgba(255, 255, 255, 0.2);
        }
    }
    @media (prefers-color-scheme: dark) {
        body:not([data-themes]) &--bg-glass {
            background: rgba(255, 255, 255, 0.08);
            border-color: rgba(255, 255, 255, 0.1);
            &:hover {
                background: rgba(255, 255, 255, 0.14);
                border-color: rgba(255, 255, 255, 0.2);
            }
        }
    }

    // Card background: solid (original look)
    &--bg-solid {
        background: var(--color-background-dark);
        border: 1px solid var(--color-border);
        &:hover {
            background: var(--color-background-hover);
            border-color: var(--color-border-dark);
        }
    }

    // Card background: flat
    &--bg-flat {
        background: var(--color-background-dark);
        border: none;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
        &:hover {
            background: var(--color-background-hover);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
    }

    // Card background: transparent
    &--bg-transparent {
        background: transparent;
        border: none;
        &:hover {
            background: var(--color-background-hover);
        }
    }

    // Card style: compact
    &--style-compact {
        padding: 8px 12px;
        min-height: 48px;
        .service-card__content { gap: 8px; }
        .service-card__name { font-size: 13px; }
        .service-card__description { font-size: 11px; }
    }
    &--style-compact#{&}--edit { padding-left: 28px; }

    // Card style: minimal
    &--style-minimal {
        padding: 6px 10px;
        min-height: unset;
        background: transparent;
        border: none;
        border-radius: 8px;
        box-shadow: none;
        .service-card__content { gap: 8px; }
        .service-card__name { font-size: 13px; }
        &:hover { background: var(--color-background-hover); transform: none; box-shadow: none; }
    }
    &--style-minimal#{&}--edit { padding-left: 24px; border: 1px dashed var(--color-border); }

    // Status style: basic — colored left border (must come after background/style rules to win specificity)
    &--status-online { border-left: 3px solid #22c55e; }
    &--status-offline { border-left: 3px solid #ef4444; }
    &--status-unknown { border-left: 3px solid #a3a3a3; }

    &__content {
        display: flex; align-items: center; gap: 12px; width: 100%; min-width: 0;
    }
    &__icon { flex-shrink: 0; }
    &__info { display: flex; flex-direction: column; min-width: 0; }
    &__name {
        font-size: 14px; font-weight: 600; color: var(--color-main-text);
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    &__description {
        font-size: 12px; color: var(--color-text-maxcontrast);
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-top: 2px;
    }

    &__history-bars {
        position: absolute;
        bottom: 6px;
        left: 8px;
        right: 8px;
        display: flex;
        flex-direction: row;
        justify-content: flex-end;
        overflow: hidden;
        gap: 2px;
    }
    &--edit &__history-bars { left: 34px; }
    &__history-bar {
        flex-shrink: 0;
        width: 2px;
        height: 7px;
        border-radius: 1px;
        &--online { background: #22c55e; }
        &--offline { background: #ef4444; }
        &--unknown { background: #a3a3a3; }
    }
}
</style>
