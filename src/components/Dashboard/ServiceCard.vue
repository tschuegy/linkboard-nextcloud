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
            statusClass,
        ]"
        @click="$emit('click')">

        <!-- Drag handle (edit mode only) -->
        <span v-if="editMode" class="service-card__drag-handle" @click.stop>
            <DragIcon :size="16" />
        </span>

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
            v-if="service.status && service.pingEnabled && statusStyle === 'dot'"
            class="service-card__status"
            :class="'service-card__status--' + service.status.status"
            :title="statusTooltip" />

        <div class="service-card__content">
            <ServiceIcon
                :icon="service.icon"
                :name="service.name"
                :color="service.iconColor"
                :size="iconSize"
                class="service-card__icon" />
            <div class="service-card__info">
                <span class="service-card__name">{{ service.name }}</span>
                <span v-if="service.description && cardStyle !== 'minimal'" class="service-card__description">
                    {{ service.description }}
                </span>
            </div>
        </div>
        <WidgetContainer
            v-if="service.widgetType && widgetData"
            :data="widgetData.data || {}"
            :field-labels="widgetData.fieldLabels || {}"
            :error="widgetData.error || null" />
    </div>
</template>

<script>
import { t } from '@nextcloud/l10n'
import ServiceIcon from '../Shared/ServiceIcon.vue'
import WidgetContainer from '../Widget/WidgetContainer.vue'
import PencilIcon from 'vue-material-design-icons/Pencil.vue'
import DragIcon from 'vue-material-design-icons/DragVertical.vue'

export default {
    name: 'ServiceCard',
    components: { ServiceIcon, WidgetContainer, PencilIcon, DragIcon },
    props: {
        service: { type: Object, required: true },
        editMode: { type: Boolean, default: false },
        cardStyle: { type: String, default: 'default' },
        statusStyle: { type: String, default: 'dot' },
        widgetData: { type: Object, default: null },
    },
    methods: {
        t,
    },
    computed: {
        iconSize: function() {
            if (this.cardStyle === 'compact') return 28
            if (this.cardStyle === 'minimal') return 24
            return 40
        },
        statusClass: function() {
            if (this.statusStyle !== 'basic' || !this.service.status || !this.service.pingEnabled) return ''
            return 'service-card--status-' + this.service.status.status
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
    background: var(--color-background-dark);
    border: 1px solid var(--color-border);
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.15s ease;
    min-height: 64px;

    &:hover {
        background: var(--color-background-hover);
        border-color: var(--color-border-dark);
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

    &__drag-handle {
        position: absolute;
        left: 8px;
        top: 50%;
        transform: translateY(-50%);
        cursor: grab;
        color: var(--color-text-maxcontrast);
        opacity: 0.4;
        transition: opacity 0.15s;
        display: flex;
        align-items: center;
        &:hover { opacity: 1; color: var(--color-primary); }
        &:active { cursor: grabbing; }
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
        &--online { background: #22c55e; box-shadow: 0 0 4px rgba(34, 197, 94, 0.6); }
        &--offline { background: #ef4444; box-shadow: 0 0 4px rgba(239, 68, 68, 0.6); }
        &--unknown { background: #a3a3a3; }
    }

    &--edit &__status { left: 34px; }

    // Status style: basic — colored left border
    &--status-online { border-left: 3px solid #22c55e; }
    &--status-offline { border-left: 3px solid #ef4444; }
    &--status-unknown { border-left: 3px solid #a3a3a3; }

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
}
</style>
