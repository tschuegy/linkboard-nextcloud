∑∑<!--
LinkBoard - DashboardView.vue
Main dashboard view with drag & drop and keyboard shortcuts

SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
    <div class="linkboard" tabindex="-1" ref="dashboard">
        <div v-if="effectiveBackgroundUrl" class="linkboard__bg" :style="bgStyle"></div>
        <div class="linkboard__content">
        <!-- Top Bar -->
        <div class="linkboard__header">
            <h2 class="linkboard__title">
                {{ settings.title || 'LinkBoard' }}
            </h2>
            <div class="linkboard__header-actions">
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
                    :aria-label="editMode ? t('linkboard', 'Stop editing') : t('linkboard', 'Edit dashboard')"
                    @click="toggleEditMode">
                    <template #icon>
                        <PencilIcon :size="20" />
                    </template>
                    {{ editMode ? t('linkboard', 'Done') : t('linkboard', 'Edit') }}
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
            <CategoryGroup
                v-for="category in displayCategories"
                :key="category.id"
                :data-category-id="String(category.id)"
                :category="category"
                :edit-mode="editMode"
                :max-columns="settings.max_columns"
                :card-style="settings.card_style"
                :status-style="settings.status_style"
                :show-count="settings.show_category_count !== 'false'"
                @edit-category="selectCategoryForEdit(category.id)"
                @edit-service="selectServiceForEdit"
                @delete-category="handleDeleteCategory(category.id)"
                @add-service="showNewServiceDialog(category.id)"
                @reorder-services="handleReorderServices"
                @service-moved="handleServiceMoved" />

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
            v-if="showNewCategory"
            :name="t('linkboard', 'New category')"
            @close="showNewCategory = false">
            <div class="linkboard__dialog-form">
                <NcTextField v-model="newCategoryName" :label="t('linkboard', 'Name')" :placeholder="t('linkboard', 'e.g. Proxmox, Switches, ...')" />
                <NcTextField v-model="newCategoryIcon" :label="t('linkboard', 'Icon (optional)')" :placeholder="t('linkboard', 'e.g. proxmox.png or mdi-server')" />
            </div>
            <template #actions>
                <NcButton @click="showNewCategory = false">{{ t('linkboard', 'Cancel') }}</NcButton>
                <NcButton type="primary" @click="handleCreateCategory">{{ t('linkboard', 'Create') }}</NcButton>
            </template>
        </NcDialog>

        <!-- New Service Dialog -->
        <NcDialog
            v-if="showNewService"
            :name="t('linkboard', 'New service')"
            @close="showNewService = false">
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
        </div>
    </div>
</template>

<script>
import { t } from '@nextcloud/l10n'
import Sortable from 'sortablejs'
import { NcButton, NcLoadingIcon, NcNoteCard, NcDialog, NcTextField } from '@nextcloud/vue'
import { mapState, mapActions } from 'pinia'
import { useDashboardStore } from '../../store/dashboard.js'
import CategoryGroup from './CategoryGroup.vue'
import ServiceEditor from '../Editor/ServiceEditor.vue'
import CategoryEditor from '../Editor/CategoryEditor.vue'
import SearchBar from './SearchBar.vue'
import EmptyState from '../Shared/EmptyState.vue'
import PencilIcon from 'vue-material-design-icons/Pencil.vue'
import CogIcon from 'vue-material-design-icons/Cog.vue'
import PlusIcon from 'vue-material-design-icons/Plus.vue'
import RefreshIcon from 'vue-material-design-icons/Refresh.vue'

export default {
    name: 'DashboardView',

    components: {
        NcButton, NcLoadingIcon, NcNoteCard, NcDialog, NcTextField,
        CategoryGroup, ServiceEditor, CategoryEditor, SearchBar, EmptyState,
        PencilIcon, CogIcon, PlusIcon, RefreshIcon,
    },

    data() {
        return {
            showNewCategory: false,
            newCategoryName: '',
            newCategoryIcon: '',
            showNewService: false,
            newServiceCategoryId: null,
            newService: { name: '', description: '', href: '', icon: '' },
            showShortcutHint: false,
            categorySortable: null,
        }
    },

    computed: {
        ...mapState(useDashboardStore, [
            'categories', 'settings', 'loading', 'error',
            'editMode', 'editingService', 'editingCategory',
            'statusChecking', 'pingEnabledCount',
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
            }
            return false
        },
    },

    watch: {
        editMode(isEdit) {
            this.$nextTick(() => {
                if (isEdit) {
                    this.initCategorySortable()
                } else {
                    this.destroyCategorySortable()
                }
            })
        },
        loading(isLoading) {
            if (!isLoading && this.editMode) {
                this.$nextTick(() => this.initCategorySortable())
            }
        },
    },

    mounted() {
        this.fetchDashboard()
        document.addEventListener('keydown', this.handleGlobalKeydown)
    },

    beforeDestroy() {
        document.removeEventListener('keydown', this.handleGlobalKeydown)
        this.destroyCategorySortable()
    },

    methods: {
        t,
        ...mapActions(useDashboardStore, [
            'fetchDashboard', 'createCategory', 'updateCategory', 'deleteCategory',
            'createService', 'updateService', 'deleteService',
            'toggleEditMode', 'selectServiceForEdit', 'selectCategoryForEdit',
            'clearSelection', 'clearError', 'checkAllStatuses',
            'reorderCategories', 'reorderServices', 'moveService',
            'fetchAllWidgetData',
        ]),

        handleRefreshAll() {
            if (this.pingEnabledCount > 0) this.checkAllStatuses()
            if (this.hasWidgets) this.fetchAllWidgetData()
        },

        // SortableJS: Category-level drag
        initCategorySortable() {
            const el = this.$refs.categoryList
            if (!el) return
            this.categorySortable = Sortable.create(el, {
                animation: 250,
                handle: '.category-group__drag-handle',
                ghostClass: 'category-group--ghost',
                filter: '.linkboard__add-category',
                onEnd: (evt) => {
                    if (evt.oldIndex === evt.newIndex) return
                    const store = useDashboardStore()
                    const cats = [...store.categories]
                    const moved = cats.splice(evt.oldIndex, 1)[0]
                    cats.splice(evt.newIndex, 0, moved)
                    store.categories = cats
                    const order = Object.fromEntries(cats.map((cat, idx) => [cat.id, idx]))
                    this.reorderCategories(order).catch((err) => {
                        this.fetchDashboard()
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

        // Drag & Drop: Services
        async handleReorderServices({ categoryId, services }) {
            const store = useDashboardStore()
            const cat = store.categories.find(c => c.id === categoryId)
            if (cat) {
                cat.services = services
                const order = Object.fromEntries(services.map((svc, idx) => [svc.id, idx]))
                try { await this.reorderServices(order) }
                catch (err) { await this.fetchDashboard() }
            }
        },

        async handleServiceMoved({ serviceId, toCategoryId }) {
            try { await this.moveService(serviceId, toCategoryId) }
            catch (err) { await this.fetchDashboard() }
        },

        // CRUD
        showNewCategoryDialog() {
            this.newCategoryName = ''
            this.newCategoryIcon = ''
            this.showNewCategory = true
        },
        async handleCreateCategory() {
            if (!this.newCategoryName.trim()) return
            await this.createCategory({ name: this.newCategoryName.trim(), icon: this.newCategoryIcon.trim() || null })
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
            if (confirm(t('linkboard', 'Really delete category and all services?'))) { await this.deleteCategory(id) }
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
    padding: 20px 32px;
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
    &__grid { display: flex; flex-direction: column; gap: 32px; }

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
}

.category-group--ghost { opacity: 0.3; background: var(--color-primary-element-light); border-radius: 12px; }

@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
.spin { animation: spin 1s linear infinite; }
.fade-enter-active, .fade-leave-active { transition: opacity 0.3s; }
.fade-enter, .fade-leave-to { opacity: 0; }
</style>
