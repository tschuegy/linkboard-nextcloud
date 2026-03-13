<!--
LinkBoard - CategoryGroup.vue
Category with SortableJS drag & drop for services

SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
    <div class="category-group">
        <!-- Spacer: view mode -->
        <template v-if="isSpacer && !editMode">
            <hr v-if="!isUnicodeSpacer" class="category-group__spacer" :style="spacerBorderStyle">
            <div v-else class="category-group__spacer category-group__spacer--unicode">{{ spacerUnicodeText }}</div>
        </template>

        <!-- Spacer: edit mode -->
        <template v-else-if="isSpacer && editMode">
            <div class="category-group__header category-group__header--spacer">
                <div class="category-group__title-row">
                    <span class="category-group__drag-handle drag-handle">
                        <DragIcon :size="18" />
                    </span>
                    <h3 class="category-group__name category-group__name--spacer">{{ category.name }}</h3>
                    <span class="category-group__spacer-badge">{{ t('linkboard', 'Spacer') }}</span>
                </div>
                <div class="category-group__actions">
                    <NcButton type="tertiary" :aria-label="t('linkboard', 'Edit category')" @click="$emit('edit-category', category.id)">
                        <template #icon><PencilIcon :size="20" /></template>
                    </NcButton>
                    <NcButton type="tertiary" :aria-label="t('linkboard', 'Delete category')" @click="$emit('delete-category', category.id)">
                        <template #icon><DeleteIcon :size="20" /></template>
                    </NcButton>
                </div>
            </div>
        </template>

        <!-- Resources: view mode -->
        <template v-else-if="isResources && !editMode">
            <div class="category-group__header">
                <div class="category-group__title-row">
                    <ServiceIcon v-if="category.icon" :icon="category.icon" :size="24" class="category-group__icon" />
                    <h3 class="category-group__name">{{ category.name }}</h3>
                </div>
            </div>
            <ResourceDisplay :data="resourceData" :config="category.config || {}" />
        </template>

        <!-- Resources: edit mode -->
        <template v-else-if="isResources && editMode">
            <div class="category-group__header category-group__header--spacer">
                <div class="category-group__title-row">
                    <span class="category-group__drag-handle drag-handle">
                        <DragIcon :size="18" />
                    </span>
                    <ServiceIcon v-if="category.icon" :icon="category.icon" :size="24" class="category-group__icon" />
                    <h3 class="category-group__name">{{ category.name }}</h3>
                    <span class="category-group__spacer-badge">{{ t('linkboard', 'Resources') }}</span>
                </div>
                <div class="category-group__actions">
                    <NcButton type="tertiary" :aria-label="t('linkboard', 'Edit category')" @click="$emit('edit-category', category.id)">
                        <template #icon><PencilIcon :size="20" /></template>
                    </NcButton>
                    <NcButton type="tertiary" :aria-label="t('linkboard', 'Delete category')" @click="$emit('delete-category', category.id)">
                        <template #icon><DeleteIcon :size="20" /></template>
                    </NcButton>
                </div>
            </div>
            <ResourceDisplay :data="resourceData" :config="category.config || {}" />
        </template>

        <!-- Normal category -->
        <template v-else>
            <div class="category-group__header">
                <div class="category-group__title-row" @click="toggleCollapse">
                    <span v-if="editMode" class="category-group__drag-handle drag-handle">
                        <DragIcon :size="18" />
                    </span>
                    <ServiceIcon
                        v-if="category.icon"
                        :icon="category.icon"
                        :size="24"
                        class="category-group__icon" />
                    <h3 class="category-group__name" :style="manualColors.category ? { color: manualColors.category } : {}">{{ category.name }}</h3>
                    <span v-if="showCount" class="category-group__count">{{ category.services.length }}</span>
                    <ChevronDownIcon
                        :size="20"
                        class="category-group__chevron"
                        :class="{ 'category-group__chevron--collapsed': isCollapsed }" />
                </div>
                <div v-if="editMode" class="category-group__actions">
                    <NcButton type="tertiary" :aria-label="t('linkboard', 'Add service')" @click="$emit('add-service', category.id)">
                        <template #icon><PlusIcon :size="20" /></template>
                    </NcButton>
                    <NcButton type="tertiary" :aria-label="t('linkboard', 'Edit category')" @click="$emit('edit-category', category.id)">
                        <template #icon><PencilIcon :size="20" /></template>
                    </NcButton>
                    <NcButton type="tertiary" :aria-label="t('linkboard', 'Delete category')" @click="$emit('delete-category', category.id)">
                        <template #icon><DeleteIcon :size="20" /></template>
                    </NcButton>
                </div>
            </div>

            <transition name="collapse">
                <div v-show="!isCollapsed">
                    <div
                        ref="serviceGrid"
                        class="category-group__grid"
                        :style="gridStyle">
                        <ServiceCard
                            v-for="service in category.services"
                            :key="service.id"
                            :class="{ 'service-card--wide': service.widgetType === 'resources' }"
                            :data-service-id="String(service.id)"
                            :service="service"
                            :edit-mode="editMode"
                            :card-style="cardStyle"
                            :card-background="cardBackground"
                            :status-style="statusStyle"
                            :widget-data="getWidgetData(service.id)"
                            :show-status-bars="showStatusBars"
                            :status-bars-opacity="statusBarsOpacity"
                            :manual-colors="manualColors"
                            @click="handleServiceClick(service)"
                            @edit="$emit('edit-service', service.id)"
                            @status-click="$emit('status-click', $event)" />
                    </div>

                </div>
            </transition>
        </template>
    </div>
</template>

<script>
import { t } from '@nextcloud/l10n'
import Sortable from 'sortablejs'
import { NcButton } from '@nextcloud/vue'
import { useDashboardStore } from '../../store/dashboard.js'
import { isUnicodeStyle, getSpacerChar, SPACER_CHARS } from '../../utils/spacerStyles.js'
import ServiceCard from './ServiceCard.vue'
import ResourceDisplay from './ResourceDisplay.vue'
import ServiceIcon from '../Shared/ServiceIcon.vue'
import PlusIcon from 'vue-material-design-icons/Plus.vue'
import PencilIcon from 'vue-material-design-icons/Pencil.vue'
import DeleteIcon from 'vue-material-design-icons/Delete.vue'
import ChevronDownIcon from 'vue-material-design-icons/ChevronDown.vue'
import DragIcon from 'vue-material-design-icons/DragVertical.vue'

var COLLAPSE_KEY = 'linkboard-collapsed'

export default {
    name: 'CategoryGroup',

    components: {
        NcButton, ServiceCard, ServiceIcon, ResourceDisplay,
        PlusIcon, PencilIcon, DeleteIcon, ChevronDownIcon, DragIcon,
    },

    props: {
        category: { type: Object, required: true },
        editMode: { type: Boolean, default: false },
        maxColumns: { type: [String, Number], default: null },
        cardStyle: { type: String, default: 'default' },
        cardBackground: { type: String, default: 'glass' },
        statusStyle: { type: String, default: 'dot' },
        showCount: { type: Boolean, default: true },
        spacerStyle: { type: String, default: 'solid' },
        showStatusBars: { type: Boolean, default: true },
        statusBarsOpacity: { type: String, default: '0.8' },
        manualColors: { type: Object, default: function() { return {} } },
    },

    data: function() {
        return {
            isCollapsed: this.loadCollapsed(),
            sortableInstance: null,
        }
    },

    computed: {
        isSpacer: function() {
            return this.category.type === 'spacer'
        },
        isResources: function() {
            return this.category.type === 'resources'
        },
        resourceData: function() {
            var store = useDashboardStore()
            return store.resourceData[this.category.id] || null
        },
        isUnicodeSpacer: function() {
            return isUnicodeStyle(this.spacerStyle)
        },
        spacerBorderStyle: function() {
            return { borderTopStyle: this.spacerStyle }
        },
        spacerUnicodeText: function() {
            var ch = getSpacerChar(this.spacerStyle)
            if (this.spacerStyle === 'fade') {
                var segment = SPACER_CHARS.fade
                var text = ''
                for (var i = 0; i < 8; i++) { text += segment }
                return text
            }
            var sep = (this.spacerStyle === 'dots' || this.spacerStyle === 'stars' || this.spacerStyle === 'diamonds' || this.spacerStyle === 'arrows') ? ' ' : ''
            var result = ''
            for (var j = 0; j < 80; j++) { result += (j > 0 ? sep : '') + ch }
            return result
        },
        gridStyle: function() {
            var cols = this.category.columns
            if (cols) {
                return { gridTemplateColumns: 'repeat(' + cols + ', 1fr)' }
            }
            var max = parseInt(this.maxColumns)
            if (max > 0) {
                return {
                    gridTemplateColumns: 'repeat(auto-fill, minmax(max(200px, calc((100% - ' + (max - 1) * 12 + 'px) / ' + max + ')), 1fr))',
                }
            }
            return {}
        },
    },

    watch: {
        editMode: function(isEdit) {
            var self = this
            this.$nextTick(function() {
                if (isEdit && !self.isCollapsed) {
                    self.initSortable()
                } else {
                    self.destroySortable()
                }
            })
        },
        isCollapsed: function(val) {
            var self = this
            this.saveCollapsed(val)
            if (!val && this.editMode) {
                this.$nextTick(function() {
                    self.initSortable()
                })
            } else {
                this.destroySortable()
            }
        },
    },

    mounted: function() {
        if (this.editMode && !this.isCollapsed) {
            var self = this
            this.$nextTick(function() {
                self.initSortable()
            })
        }
    },

    beforeDestroy: function() {
        this.destroySortable()
    },

    methods: {
        t,
        getWidgetData: function(serviceId) {
            var store = useDashboardStore()
            return store.widgetData[serviceId] || null
        },

        toggleCollapse: function() {
            this.isCollapsed = !this.isCollapsed
        },

        handleServiceClick: function(service) {
            if (this.editMode) {
                this.$emit('edit-service', service.id)
            } else if (service.href) {
                window.open(service.href, service.target || '_blank')
            }
        },

        initSortable: function() {
            this.destroySortable()
            var el = this.$refs.serviceGrid
            if (!el) return
            var self = this

            this.sortableInstance = Sortable.create(el, {
                group: 'services',
                animation: 200,
                handle: '.service-card__drag-handle',
                ghostClass: 'service-card--ghost',
                onEnd: function(evt) {
                    var serviceId = parseInt(evt.item.dataset.serviceId)
                    var fromCatEl = evt.from.closest('[data-category-id]')
                    var toCatEl = evt.to.closest('[data-category-id]')
                    var fromCatId = fromCatEl ? parseInt(fromCatEl.dataset.categoryId) : null
                    var toCatId = toCatEl ? parseInt(toCatEl.dataset.categoryId) : null

                    if (fromCatId !== toCatId && toCatId) {
                        self.$emit('service-moved', { serviceId: serviceId, toCategoryId: toCatId })
                    } else if (evt.oldIndex !== evt.newIndex) {
                        var services = self.category.services.slice()
                        var moved = services.splice(evt.oldIndex, 1)[0]
                        services.splice(evt.newIndex, 0, moved)
                        self.$emit('reorder-services', {
                            categoryId: self.category.id,
                            services: services,
                        })
                    }
                },
            })
        },

        destroySortable: function() {
            if (this.sortableInstance) {
                this.sortableInstance.destroy()
                this.sortableInstance = null
            }
        },


        loadCollapsed: function() {
            try {
                var stored = JSON.parse(localStorage.getItem(COLLAPSE_KEY) || '{}')
                return !!stored[this.category.id]
            } catch (e) { return false }
        },

        saveCollapsed: function(val) {
            try {
                var stored = JSON.parse(localStorage.getItem(COLLAPSE_KEY) || '{}')
                if (val) { stored[this.category.id] = true } else { delete stored[this.category.id] }
                localStorage.setItem(COLLAPSE_KEY, JSON.stringify(stored))
            } catch (e) { /* ignore */ }
        },
    },
}
</script>

<style lang="scss" scoped>
.category-group {
    transition: background 0.2s;
    border-radius: 12px;
    padding: 4px;

    &__header {
        display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;
    }
    &__title-row {
        display: flex; align-items: center; gap: 8px; cursor: pointer; user-select: none;
    }
    &__drag-handle {
        cursor: grab; color: var(--color-text-maxcontrast); opacity: 0.5;
        transition: opacity 0.15s; display: flex; align-items: center;
        &:hover { opacity: 1; }
        &:active { cursor: grabbing; }
    }
    &__icon { flex-shrink: 0; }
    &__name { font-size: 18px; font-weight: 600; margin: 0; color: var(--color-main-text); }
    &__count {
        font-size: 13px; color: var(--color-text-maxcontrast);
        background: var(--color-background-dark); padding: 1px 8px; border-radius: 10px;
    }
    &__chevron {
        color: var(--color-text-maxcontrast); transition: transform 0.2s ease;
        &--collapsed { transform: rotate(-90deg); }
    }
    &__actions { display: flex; gap: 2px; }
    &__grid {
        display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 12px; min-height: 40px;

        .service-card--wide {
            grid-column: span 2;
        }
    }
    &__spacer {
        border: none;
        border-top: 2px solid var(--color-border);
        margin: 8px 0;

        &--unicode {
            border: none;
            overflow: hidden;
            white-space: nowrap;
            text-align: center;
            color: var(--color-border);
            font-size: 14px;
            line-height: 1;
            letter-spacing: 0;
        }
    }
    &__spacer-badge {
        font-size: 11px; color: var(--color-text-maxcontrast);
        background: var(--color-background-dark); padding: 1px 8px; border-radius: 10px;
    }
    &__header--spacer {
        opacity: 0.6;
    }
    &__name--spacer {
        color: var(--color-text-maxcontrast);
    }
}

.collapse-enter-active, .collapse-leave-active { transition: all 0.25s ease; overflow: hidden; }
.collapse-enter, .collapse-leave-to { opacity: 0; max-height: 0; }
.collapse-enter-to, .collapse-leave { opacity: 1; max-height: 2000px; }
</style>
