∑∑<!--
LinkBoard - DashboardView.vue
Main dashboard view with drag & drop and keyboard shortcuts

SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
    <div class="linkboard" :class="{ 'linkboard--light-text': isLightTextMode }" tabindex="-1" ref="dashboard">
        <div v-if="effectiveBackgroundUrl" class="linkboard__bg" :style="bgStyle"></div>
        <div class="linkboard__content">
        <!-- Top Bar -->
        <div class="linkboard__header">
            <h2 class="linkboard__title" :style="manualColors.title ? { color: manualColors.title } : {}">
                {{ settings.title || 'LinkBoard' }}
            </h2>
            <div class="linkboard__header-actions"
                 :class="{ 'has-custom-color': manualColors.headerButton }"
                 :style="manualColors.headerButton ? { '--header-btn-color': manualColors.headerButton } : {}">
                <SearchBar
                    v-if="settings.show_search !== 'false'"
                    ref="searchBar"
                    v-model="searchQuery" />
                <NcButton
                    v-if="pingEnabledCount > 0 || hasWidgets"
                    type="tertiary"
                    :aria-label="t('linkboard', 'Check status of all services')"
                    :disabled="statusChecking"
                    @click="handleRefreshAll">
                    <template #icon>
                        <RefreshIcon :size="20" :class="{ 'spin': statusChecking }" />
                    </template>
                </NcButton>
                <NcButton
                    :type="editMode ? 'primary' : 'tertiary'"
                    :aria-label="editMode ? t('linkboard', 'Lock layout') : t('linkboard', 'Unlock layout')"
                    @click="toggleEditMode">
                    <template #icon>
                        <LockOpenVariantIcon v-if="editMode" :size="20" />
                        <LockIcon v-else :size="20" />
                    </template>
                    {{ editMode ? t('linkboard', 'Done') : t('linkboard', 'Edit') }}
                </NcButton>
                <NcButton
                    v-if="pingEnabledCount > 0"
                    type="tertiary"
                    :aria-label="t('linkboard', 'Status overview')"
                    @click="$router.push('/status')">
                    <template #icon>
                        <ChartLineIcon :size="20" />
                    </template>
                </NcButton>
                <NcButton
                    type="tertiary"
                    :aria-label="t('linkboard', 'Settings')"
                    @click="$router.push('/settings')">
                    <template #icon>
                        <CogIcon :size="20" />
                    </template>
                </NcButton>
            </div>
        </div>

        <!-- Keyboard shortcuts hint -->
        <transition name="fade">
            <div v-if="showShortcutHint" class="linkboard__shortcut-hint">
                <span><kbd>/</kbd> {{ t('linkboard', 'Search') }}</span>
                <span><kbd>Esc</kbd> {{ t('linkboard', 'Close') }}</span>
                <span><kbd>E</kbd> {{ t('linkboard', 'Edit') }}</span>
                <span>{{ t('linkboard', 'Drag & drop to sort') }}</span>
            </div>
        </transition>

        <!-- Error Banner -->
        <NcNoteCard v-if="error" type="error" @close="clearError">
            {{ error }}
        </NcNoteCard>

        <!-- Loading -->
        <NcLoadingIcon v-if="loading" :size="64" class="linkboard__loading" />

        <!-- Empty State -->
        <EmptyState
            v-else-if="!loading && categories.length === 0"
            @create="showNewCategoryDialog" />

        <!-- Dashboard Grid -->
        <div v-else ref="categoryList" class="linkboard__grid">
            <div v-for="cat in displayCategories" :key="cat.id"
                 :data-category-id="String(cat.id)"
                 class="linkboard__row">
                <span v-if="editMode" class="linkboard__row-handle">
                    <DragVerticalIcon :size="18" />
                </span>
                <CategoryGroup
                    :data-category-id="String(cat.id)"
                    :category="stripChildren(cat)"
                    :edit-mode="editMode"
                    :max-columns="settings.max_columns"
                    :card-style="settings.card_style"
                    :card-background="settings.card_background || 'glass'"
                    :status-style="settings.status_style"
                    :show-count="settings.show_category_count !== 'false'"
                    :spacer-style="settings.spacer_style || 'solid'"
                    :show-status-bars="settings.show_status_bars !== 'false'"
                    :status-bars-opacity="settings.status_bars_opacity || '0.8'"
                    :manual-colors="manualColors"
                    @edit-category="selectCategoryForEdit"
                    @edit-service="selectServiceForEdit"
                    @delete-category="handleDeleteCategory"
                    @add-service="showNewServiceDialog"
                    @status-click="openStatusHistory" />
                <CategoryGroup
                    v-for="child in (cat.children || [])" :key="child.id"
                    :data-category-id="String(child.id)"
                    :category="child"
                    :edit-mode="editMode"
                    :max-columns="settings.max_columns"
                    :card-style="settings.card_style"
                    :card-background="settings.card_background || 'glass'"
                    :status-style="settings.status_style"
                    :show-count="settings.show_category_count !== 'false'"
                    :spacer-style="settings.spacer_style || 'solid'"
                    :show-status-bars="settings.show_status_bars !== 'false'"
                    :status-bars-opacity="settings.status_bars_opacity || '0.8'"
                    :manual-colors="manualColors"
                    @edit-category="selectCategoryForEdit"
                    @edit-service="selectServiceForEdit"
                    @delete-category="handleDeleteCategory"
                    @add-service="showNewServiceDialog"
                    @status-click="openStatusHistory" />
            </div>

            <div v-if="editMode" class="linkboard__add-category" @click="showNewCategoryDialog">
                <PlusIcon :size="32" />
                <span>{{ t('linkboard', 'New category') }}</span>
            </div>
        </div>

        <!-- Editor Sidebar -->
        <ServiceEditor
            v-if="editMode && editingService"
            :service="editingService"
            :categories="categories"
            @save="handleSaveService"
            @close="clearSelection"
            @delete="handleDeleteService" />

        <CategoryEditor
            v-if="editMode && editingCategory"
            :category="editingCategory"
            @save="handleSaveCategory"
            @close="clearSelection" />

        <!-- New Category Dialog -->
        <NcDialog
            :open="showNewCategory"
            :name="t('linkboard', 'New category')"
            @update:open="showNewCategory = $event">
            <div class="linkboard__dialog-form">
                <NcTextField v-model="newCategoryName" :label="t('linkboard', 'Name')" :placeholder="t('linkboard', 'e.g. Proxmox, Switches, ...')" />
                <NcTextField v-if="newCategoryType === 'default'" v-model="newCategoryIcon" :label="t('linkboard', 'Icon (optional)')" :placeholder="t('linkboard', 'e.g. proxmox.png or mdi-server')" />
                <div class="linkboard__dialog-field">
                    <label>{{ t('linkboard', 'Category type') }}</label>
                    <NcSelect v-model="newCategoryType" :options="typeOptions" :clearable="false" label="label" :reduce="opt => opt.id" />
                </div>
            </div>
            <template #actions>
                <NcButton @click="showNewCategory = false">{{ t('linkboard', 'Cancel') }}</NcButton>
                <NcButton type="primary" @click="handleCreateCategory">{{ t('linkboard', 'Create') }}</NcButton>
            </template>
        </NcDialog>

        <!-- New Service Dialog -->
        <NcDialog
            :open="showNewService"
            :name="t('linkboard', 'New service')"
            @update:open="showNewService = $event">
            <div class="linkboard__dialog-form">
                <NcTextField v-model="newService.name" :label="t('linkboard', 'Name')" placeholder="z.B. SP0001016" />
                <NcTextField v-model="newService.description" :label="t('linkboard', 'Description')" placeholder="z.B. Minisforum 795S7 on RZ0" />
                <NcTextField v-model="newService.href" :label="t('linkboard', 'URL')" placeholder="https://..." />
                <NcTextField v-model="newService.icon" :label="t('linkboard', 'Icon')" placeholder="z.B. proxmox.png oder https://..." />
            </div>
            <template #actions>
                <NcButton @click="showNewService = false">{{ t('linkboard', 'Cancel') }}</NcButton>
                <NcButton type="primary" @click="handleCreateService">{{ t('linkboard', 'Create') }}</NcButton>
            </template>
        </NcDialog>
        <!-- Status History Modal -->
        <StatusHistoryModal
            :open="showStatusHistory"
            :service-id="statusHistoryServiceId"
            :service-name="statusHistoryServiceName"
            @update:open="showStatusHistory = $event" />

        <div v-if="appVersion" class="linkboard__version-footer" :style="manualColors.versionFooter ? { color: manualColors.versionFooter } : {}">
            <a :href="latestVersionUrl || 'https://github.com/tschuegy/linkboard-nextcloud'"
               target="_blank" rel="noopener noreferrer"
               class="linkboard__version-link">
                {{ t('linkboard', 'Version {version}', { version: appVersion }) }}
            </a>
            <span v-if="latestVersion && latestVersionUrl"
                  class="linkboard__version-update">
                {{ t('linkboard', 'New version available: {version}', { version: latestVersion }) }}
            </span>
        </div>
        </div>
    </div>
</template>

<script>
import { t } from '@nextcloud/l10n'
import Sortable from 'sortablejs'
import { NcButton, NcLoadingIcon, NcNoteCard, NcDialog, NcTextField, NcCheckboxRadioSwitch, NcSelect } from '@nextcloud/vue'
import { mapState, mapActions } from 'pinia'
import { useDashboardStore } from '../../store/dashboard.js'
import CategoryGroup from './CategoryGroup.vue'
import ServiceEditor from '../Editor/ServiceEditor.vue'
import CategoryEditor from '../Editor/CategoryEditor.vue'
import SearchBar from './SearchBar.vue'
import StatusHistoryModal from './StatusHistoryModal.vue'
import EmptyState from '../Shared/EmptyState.vue'
import LockIcon from 'vue-material-design-icons/Lock.vue'
import LockOpenVariantIcon from 'vue-material-design-icons/LockOpenVariant.vue'
import CogIcon from 'vue-material-design-icons/Cog.vue'
import PlusIcon from 'vue-material-design-icons/Plus.vue'
import RefreshIcon from 'vue-material-design-icons/Refresh.vue'
import DragVerticalIcon from 'vue-material-design-icons/DragVertical.vue'
import ChartLineIcon from 'vue-material-design-icons/ChartLine.vue'
import { detectBackgroundLuminance } from '../../utils/contrastDetect.js'

export default {
    name: 'DashboardView',

    components: {
        NcButton, NcLoadingIcon, NcNoteCard, NcDialog, NcTextField, NcCheckboxRadioSwitch, NcSelect,
        CategoryGroup, ServiceEditor, CategoryEditor, SearchBar, EmptyState, StatusHistoryModal,
        LockIcon, LockOpenVariantIcon, CogIcon, PlusIcon, RefreshIcon, DragVerticalIcon, ChartLineIcon,
    },

    data() {
        return {
            showNewCategory: false,
            newCategoryName: '',
            newCategoryIcon: '',
            newCategoryType: 'default',
            showNewService: false,
            newServiceCategoryId: null,
            newService: { name: '', description: '', href: '', icon: '' },
            typeOptions: [
                { id: 'default', label: t('linkboard', 'Default') },
                { id: 'spacer', label: t('linkboard', 'Spacer') },
                { id: 'resources', label: t('linkboard', 'Resources') },
            ],
            showShortcutHint: false,
            categorySortable: null,
            rowSortables: [],
            resourceRefreshInterval: null,
            showStatusHistory: false,
            statusHistoryServiceId: null,
            statusHistoryServiceName: '',
            detectedTextMode: null,
        }
    },

    computed: {
        ...mapState(useDashboardStore, [
            'categories', 'settings', 'loading', 'error',
            'editMode', 'editingService', 'editingCategory',
            'statusChecking', 'pingEnabledCount',
            'appVersion', 'latestVersion', 'latestVersionUrl',
        ]),
        searchQuery: {
            get() { return useDashboardStore().searchQuery },
            set(val) { useDashboardStore().searchQuery = val },
        },
        displayCategories() { return useDashboardStore().filteredCategories },
        effectiveBackgroundUrl() {
            if (this.settings.background_url) {
                return this.settings.background_url
            }
            var raw = getComputedStyle(document.documentElement).getPropertyValue('--image-background').trim()
            if (!raw || raw === 'none') {
                return null
            }
            var match = raw.match(/url\(['"]?(.*?)['"]?\)/)
            return match ? match[1] : null
        },
        bgStyle() {
            var style = {
                backgroundImage: "url('" + this.effectiveBackgroundUrl + "')",
            }
            var blur = this.settings.background_blur
            if (blur && blur !== 'none') {
                var map = { sm: '4px', md: '8px', lg: '16px', xl: '24px' }
                style.filter = 'blur(' + (map[blur] || '0px') + ')'
            }
            return style
        },
        hasWidgets() {
            for (const cat of this.categories) {
                for (const svc of (cat.services || [])) {
                    if (svc.widgetType) return true
                }
                for (const child of (cat.children || [])) {
                    for (const svc of (child.services || [])) {
                        if (svc.widgetType) return true
                    }
                }
            }
            return false
        },
        hasResources() {
            for (const cat of this.categories) {
                if (cat.type === 'resources') return true
                for (const child of (cat.children || [])) {
                    if (child.type === 'resources') return true
                }
            }
            return false
        },
        effectiveFontColorMode() {
            if (this.settings.font_color_mode) return this.settings.font_color_mode
            if (this.settings.theme === 'manual') return 'manual'
            return 'auto'
        },
        manualColors() {
            if (this.effectiveFontColorMode === 'manual') {
                return {
                    title: this.settings.manual_color_title || null,
                    category: this.settings.manual_color_category || null,
                    service: this.settings.manual_color_service || null,
                    description: this.settings.manual_color_description || null,
                    widgetValue: this.settings.manual_color_widget_value || null,
                    widgetLabel: this.settings.manual_color_widget_label || null,
                    cardBg: this.settings.manual_color_card_bg || null,
                    headerButton: this.settings.manual_color_header_button || null,
                }
            }
            if (this.effectiveFontColorMode === 'auto' && this.detectedTextMode === 'light') {
                return {
                    title: '#ffffff',
                    category: '#ffffff',
                    service: '#ffffff',
                    description: 'rgba(255,255,255,0.7)',
                    widgetValue: '#ffffff',
                    widgetLabel: 'rgba(255,255,255,0.7)',
                    versionFooter: 'rgba(255,255,255,0.7)',
                    cardBg: null,
                    headerButton: '#ffffff',
                }
            }
            return {}
        },
        isLightTextMode() {
            return this.effectiveFontColorMode === 'auto' && this.detectedTextMode === 'light'
        },
    },

    watch: {
        editMode(isEdit) {
            this.$nextTick(() => {
                if (isEdit) {
                    this.initCategorySortable()
                    this.initRowSortables()
                } else {
                    this.destroyCategorySortable()
                    this.destroyRowSortables()
                }
            })
        },
        loading(isLoading) {
            if (!isLoading && this.editMode) {
                this.$nextTick(() => {
                    this.initCategorySortable()
                    this.initRowSortables()
                })
            }
        },
        displayCategories() {
            if (this.editMode) {
                this.$nextTick(() => this.initRowSortables())
            }
        },
        effectiveBackgroundUrl: {
            handler() { this.runLuminanceDetection() },
            immediate: true,
        },
        effectiveFontColorMode() { this.runLuminanceDetection() },
    },

    mounted() {
        this.fetchDashboard()
        document.addEventListener('keydown', this.handleGlobalKeydown)
        this.startResourceRefresh()
    },

    beforeDestroy() {
        document.removeEventListener('keydown', this.handleGlobalKeydown)
        this.destroyCategorySortable()
        this.destroyRowSortables()
        this.stopResourceRefresh()
    },

    methods: {
        t,
        ...mapActions(useDashboardStore, [
            'fetchDashboard', 'createCategory', 'updateCategory', 'deleteCategory',
            'createService', 'updateService', 'deleteService',
            'toggleEditMode', 'selectServiceForEdit', 'selectCategoryForEdit',
            'clearSelection', 'clearError', 'checkAllStatuses',
            'reorderCategories', 'reorderServices', 'moveService',
            'moveCategoryToParent', 'fetchAllWidgetData', 'fetchAllResourceData',
        ]),

        runLuminanceDetection() {
            var self = this
            if (this.effectiveFontColorMode !== 'auto') {
                this.detectedTextMode = null
                return
            }
            detectBackgroundLuminance(this.effectiveBackgroundUrl).then(function(mode) {
                self.detectedTextMode = mode
            })
        },

        handleRefreshAll() {
            if (this.pingEnabledCount > 0) this.checkAllStatuses()
            if (this.hasWidgets) this.fetchAllWidgetData()
            if (this.hasResources) this.fetchAllResourceData()
        },

        startResourceRefresh() {
            var self = this
            this.resourceRefreshInterval = setInterval(function() {
                if (self.hasResources) {
                    self.fetchAllResourceData()
                }
            }, 10000)
        },

        stopResourceRefresh() {
            if (this.resourceRefreshInterval) {
                clearInterval(this.resourceRefreshInterval)
                this.resourceRefreshInterval = null
            }
        },

        stripChildren(cat) {
            var copy = Object.assign({}, cat)
            delete copy.children
            return copy
        },

        // SortableJS: Row-level drag (reorder rows in the grid)
        initCategorySortable() {
            this.destroyCategorySortable()
            var el = this.$refs.categoryList
            if (!el) return
            var self = this
            this.categorySortable = Sortable.create(el, {
                animation: 250,
                draggable: '.linkboard__row',
                handle: '.linkboard__row-handle',
                ghostClass: 'linkboard__row--ghost',
                filter: '.linkboard__add-category',
                onEnd: function(evt) {
                    if (evt.oldIndex === evt.newIndex) return
                    var store = useDashboardStore()
                    var cats = store.categories.slice()
                    var moved = cats.splice(evt.oldIndex, 1)[0]
                    cats.splice(evt.newIndex, 0, moved)
                    store.categories = cats
                    var order = Object.fromEntries(cats.map(function(cat, idx) { return [cat.id, idx] }))
                    self.reorderCategories(order).catch(function() {
                        self.fetchDashboard()
                    })
                },
            })
        },

        destroyCategorySortable() {
            if (this.categorySortable) {
                this.categorySortable.destroy()
                this.categorySortable = null
            }
        },

        // SortableJS: Category-within-row drag (move categories between rows)
        initRowSortables() {
            this.destroyRowSortables()
            var el = this.$refs.categoryList
            if (!el) return
            var self = this
            var rows = el.querySelectorAll('.linkboard__row')
            rows.forEach(function(row) {
                var rowLeaderId = parseInt(row.dataset.categoryId)
                var s = Sortable.create(row, {
                    group: 'categories',
                    animation: 200,
                    handle: '.category-group__drag-handle',
                    ghostClass: 'category-group--ghost',
                    filter: '.linkboard__row-handle',
                    preventOnFilter: false,
                    onAdd: function(evt) {
                        var categoryId = parseInt(evt.item.dataset.categoryId)
                        self.handleRowAdd(categoryId, rowLeaderId)
                    },
                    onEnd: function() {
                        // Categories reordered within same row — no action needed
                    },
                })
                self.rowSortables.push(s)
            })
        },

        destroyRowSortables() {
            this.rowSortables.forEach(function(s) { s.destroy() })
            this.rowSortables = []
        },

        async handleRowAdd(categoryId, rowLeaderId) {
            try {
                await this.moveCategoryToParent(categoryId, rowLeaderId)
                await this.fetchDashboard()
            } catch (err) { await this.fetchDashboard() }
        },

        // Keyboard Shortcuts
        handleGlobalKeydown(e) {
            const tag = e.target.tagName
            if (tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT' || e.target.isContentEditable) {
                if (e.key === 'Escape') {
                    e.target.blur()
                    this.searchQuery = ''
                }
                return
            }
            switch (e.key) {
            case '/':
                e.preventDefault()
                this.focusSearch()
                break
            case 'Escape':
                if (this.editingService || this.editingCategory) {
                    this.clearSelection()
                } else if (this.editMode) {
                    this.toggleEditMode()
                }
                break
            case 'e':
            case 'E':
                if (!e.ctrlKey && !e.metaKey) {
                    this.toggleEditMode()
                    if (this.editMode) {
                        this.showShortcutHint = true
                        setTimeout(() => { this.showShortcutHint = false }, 3000)
                    }
                }
                break
            case 'r':
            case 'R':
                if (!e.ctrlKey && !e.metaKey && (this.pingEnabledCount > 0 || this.hasWidgets)) {
                    e.preventDefault()
                    this.handleRefreshAll()
                }
                break
            }
        },

        focusSearch() {
            const input = this.$refs.searchBar && this.$refs.searchBar.$el
                ? this.$refs.searchBar.$el.querySelector('input')
                : null
            if (input) input.focus()
        },

        // CRUD
        showNewCategoryDialog() {
            this.newCategoryName = ''
            this.newCategoryIcon = ''
            this.newCategoryType = 'default'
            this.showNewCategory = true
        },
        async handleCreateCategory() {
            if (!this.newCategoryName.trim()) return
            var payload = {
                name: this.newCategoryName.trim(),
                icon: this.newCategoryType === 'default' ? (this.newCategoryIcon.trim() || null) : null,
                type: this.newCategoryType,
            }
            if (this.newCategoryType === 'resources') {
                payload.config = JSON.stringify({
                    showCpu: true,
                    showMemory: true,
                    showUptime: true,
                    showCpuTemp: false,
                    tempUnit: 'C',
                    diskPaths: ['/'],
                })
            }
            await this.createCategory(payload)
            this.showNewCategory = false
        },
        showNewServiceDialog(categoryId) {
            this.newServiceCategoryId = categoryId
            this.newService = { name: '', description: '', href: '', icon: '' }
            this.showNewService = true
        },
        async handleCreateService() {
            if (!this.newService.name.trim()) return
            await this.createService({
                categoryId: this.newServiceCategoryId,
                name: this.newService.name.trim(),
                description: this.newService.description.trim() || null,
                href: this.newService.href.trim() || null,
                icon: this.newService.icon.trim() || null,
            })
            this.showNewService = false
        },
        async handleSaveService(sd) { await this.updateService(sd.id, sd); this.clearSelection() },
        async handleSaveCategory(cd) { await this.updateCategory(cd.id, cd); this.clearSelection() },
        async handleDeleteCategory(id) {
            var catId = typeof id === 'number' ? id : id
            if (confirm(t('linkboard', 'Really delete category and all services?'))) { await this.deleteCategory(catId) }
        },
        openStatusHistory(service) {
            this.statusHistoryServiceId = service.id
            this.statusHistoryServiceName = service.name
            this.showStatusHistory = true
        },
        async handleDeleteService(id) {
            if (confirm(t('linkboard', 'Really delete service?'))) { await this.deleteService(id); this.clearSelection() }
        },
    },
}
</script>

<style lang="scss" scoped>
.linkboard {
    position: relative;
    padding: 4px 32px;
    max-width: 1800px;
    margin: 0 auto;
    outline: none;

    &__bg {
        position: fixed;
        inset: 0;
        z-index: 0;
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
    }

    &__content {
        position: relative;
        z-index: 1;
    }

    &__header {
        display: flex; align-items: center; justify-content: space-between;
        margin-bottom: 24px; gap: 16px;
    }
    &__title { font-size: 24px; font-weight: 700; margin: 0; color: var(--color-main-text); }
    &__header-actions { display: flex; align-items: center; gap: 8px; }
    &__loading { display: flex; justify-content: center; margin-top: 80px; }
    &__grid {
        display: flex;
        flex-direction: column;
        gap: 24px;
        align-items: stretch;
    }
    &__row {
        display: flex;
        flex-wrap: wrap;
        gap: 24px;
        align-items: flex-start;
    }
    &__row > .category-group {
        flex: 1 1 0;
        min-width: 300px;
    }
    &__row-handle {
        display: flex;
        align-items: center;
        cursor: grab;
        color: var(--color-text-maxcontrast);
        opacity: 0.4;
        transition: opacity 0.15s;
        &:hover { opacity: 1; }
        &:active { cursor: grabbing; }
    }
    &__row--ghost {
        opacity: 0.3;
        background: var(--color-primary-element-light);
        border-radius: 12px;
    }
    &__shortcut-hint {
        display: flex; align-items: center; gap: 16px;
        padding: 8px 16px; background: var(--color-background-dark);
        border-radius: 8px; margin-bottom: 16px;
        font-size: 13px; color: var(--color-text-maxcontrast);
        kbd {
            display: inline-block; padding: 2px 6px;
            background: var(--color-background-hover);
            border: 1px solid var(--color-border);
            border-radius: 4px; font-family: monospace; font-size: 12px;
        }
    }

    &__add-category {
        display: flex; align-items: center; justify-content: center; gap: 8px;
        padding: 24px; border: 2px dashed var(--color-border); border-radius: 12px;
        color: var(--color-text-maxcontrast); cursor: pointer; transition: all 0.2s;
        &:hover {
            border-color: var(--color-primary); color: var(--color-primary);
            background: var(--color-primary-element-light);
        }
    }

    &__dialog-form { display: flex; flex-direction: column; gap: 12px; padding: 16px 0; }
    &__dialog-field {
        display: flex; flex-direction: column; gap: 4px;
        label { font-size: 13px; font-weight: 500; color: var(--color-text-maxcontrast); }
    }

    &__version-footer {
        display: flex;
        justify-content: flex-end;
        align-items: center;
        gap: 12px;
        margin-top: 24px;
        padding: 8px 0;
        font-size: 14px;
        color: var(--color-text-maxcontrast);
    }

    &__version-link {
        color: inherit;
        text-decoration: none;
        &:hover { text-decoration: underline; }
    }

    &__version-update {
        font-weight: bold;
    }

    &__header-actions.has-custom-color :deep(.button-vue) { color: var(--header-btn-color); }
    &--light-text &__shortcut-hint { color: rgba(255, 255, 255, 0.7); }
}

.category-group--ghost { opacity: 0.3; background: var(--color-primary-element-light); border-radius: 12px; }

@media (max-width: 600px) {
    .linkboard__row > .category-group {
        min-width: 100% !important;
    }
}

@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
.spin { animation: spin 1s linear infinite; }
.fade-enter-active, .fade-leave-active { transition: opacity 0.3s; }
.fade-enter, .fade-leave-to { opacity: 0; }
</style>
