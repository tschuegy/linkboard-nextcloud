<!--
LinkBoard - ServiceListRow.vue
Single-line service row for the compact list display mode

SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
    <div
        class="service-list-row"
        :class="[
            { 'service-list-row--edit': editMode },
            statusClass,
        ]"
        @click="$emit('click')">

        <!-- Drag handle for list reordering -->
        <span
            v-if="showDragHandle"
            class="service-list-row__drag-handle"
            role="button"
            :aria-label="t('linkboard', 'Drag to reorder')"
            @click.stop>
            <DragIcon :size="16" />
        </span>

        <!-- Status indicator dot -->
        <span
            v-if="showStatusDot"
            class="service-list-row__status"
            :class="'service-list-row__status--' + service.status.status"
            :title="statusTooltip"
            @click.stop="$emit('status-click', service)" />

        <ServiceIcon
            :icon="service.icon"
            :name="service.name"
            :color="service.iconColor"
            :size="20"
            class="service-list-row__icon" />

        <span class="service-list-row__name" :style="manualColors.service ? { color: manualColors.service } : {}">{{ primaryText }}</span>
        <span v-if="secondaryText" class="service-list-row__secondary" :style="manualColors.description ? { color: manualColors.description } : {}">{{ secondaryText }}</span>

        <!-- Edit button -->
        <button
            v-if="editMode"
            class="service-list-row__edit-btn"
            :aria-label="t('linkboard', 'Edit service {name}', { name: service.name })"
            @click.stop="$emit('edit')">
            <PencilIcon :size="16" />
        </button>
    </div>
</template>

<script>
import { t } from '@nextcloud/l10n'
import ServiceIcon from '../Shared/ServiceIcon.vue'
import PencilIcon from 'vue-material-design-icons/Pencil.vue'
import DragIcon from 'vue-material-design-icons/DragVertical.vue'
export default {
    name: 'ServiceListRow',
    components: { ServiceIcon, PencilIcon, DragIcon },
    props: {
        service: { type: Object, required: true },
        editMode: { type: Boolean, default: false },
        showDragHandle: { type: Boolean, default: false },
        rowContent: { type: String, default: 'title' },
        statusStyle: { type: String, default: 'dot' },
        manualColors: { type: Object, default: function() { return {} } },
    },
    methods: {
        t,
    },
    computed: {
        primaryText: function() {
            if (this.rowContent === 'url') {
                return this.service.href || this.service.name
            }
            return this.service.name
        },
        secondaryText: function() {
            if (this.rowContent === 'title_description') return this.service.description || ''
            if (this.rowContent === 'title_url') return this.service.href || ''
            return ''
        },
        showStatusDot: function() {
            return this.statusStyle !== 'basic' && this.service.status && this.service.pingEnabled
        },
        statusClass: function() {
            if (this.statusStyle !== 'basic' || !this.service.status || !this.service.pingEnabled) return ''
            return 'service-list-row--status-' + this.service.status.status
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
.service-list-row {
    display: flex;
    align-items: center;
    gap: 8px;
    min-height: 32px;
    padding: 4px 8px;
    border-radius: 8px;
    cursor: pointer;
    transition: background 0.15s ease;

    &:hover {
        background: var(--color-background-hover);
    }

    &--edit {
        border: 1px dashed var(--color-border);
        &:hover { border-color: var(--color-primary); }
    }

    &__drag-handle {
        flex-shrink: 0;
        display: flex; align-items: center;
        cursor: grab;
        color: var(--color-text-maxcontrast);
        opacity: 0.5;
        transition: opacity 0.15s;
        touch-action: none;
        &:hover { opacity: 1; }
        &:active { cursor: grabbing; }
    }

    &__status {
        flex-shrink: 0;
        width: 8px; height: 8px;
        border-radius: 50%;
        cursor: pointer;
        &--online { background: #22c55e; box-shadow: 0 0 4px rgba(34, 197, 94, 0.6); }
        &--offline { background: #ef4444; box-shadow: 0 0 4px rgba(239, 68, 68, 0.6); }
        &--unknown { background: #a3a3a3; }
    }

    &--status-online { border-left: 3px solid #22c55e; }
    &--status-offline { border-left: 3px solid #ef4444; }
    &--status-unknown { border-left: 3px solid #a3a3a3; }

    &__icon { flex-shrink: 0; }

    &__name {
        font-size: 13px; font-weight: 600; color: var(--color-main-text);
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        min-width: 0;
        flex-shrink: 1;
    }

    &__secondary {
        font-size: 12px; color: var(--color-text-maxcontrast);
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        min-width: 0;
        flex-shrink: 10;
    }

    &__edit-btn {
        flex-shrink: 0;
        margin-left: auto;
        width: 28px; height: 28px;
        display: flex; align-items: center; justify-content: center;
        background: var(--color-background-dark);
        border: 1px solid var(--color-border);
        border-radius: 6px; cursor: pointer;
        opacity: 0; transition: opacity 0.15s;
        color: var(--color-main-text);
        .service-list-row:hover & { opacity: 1; }
    }
}
</style>
