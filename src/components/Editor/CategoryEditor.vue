<!--
LinkBoard - CategoryEditor.vue
Sidebar panel for editing category properties

SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
    <div class="category-editor">
        <div class="category-editor__header">
            <h3>{{ t('linkboard', 'Edit category') }}</h3>
            <NcButton type="tertiary" :aria-label="t('linkboard', 'Close editor')" @click="$emit('close')">
                <template #icon>
                    <CloseIcon :size="20" />
                </template>
            </NcButton>
        </div>

        <div class="category-editor__form">
            <NcTextField v-model="form.name" :label="t('linkboard', 'Name')" />

            <div class="category-editor__field">
                <label>{{ t('linkboard', 'Category type') }}</label>
                <NcSelect v-model="form.type" :options="typeOptions" :clearable="false" label="label" :reduce="opt => opt.id" />
            </div>

            <template v-if="form.type === 'default'">
                <NcTextField v-model="form.icon" :label="t('linkboard', 'Icon')" placeholder="proxmox.png, https://..., oder mdi-server" />
                <NcTextField v-model="form.tab" :label="t('linkboard', 'Tab (optional)')" :placeholder="t('linkboard', 'Tab name for grouping')" />

                <div class="category-editor__field">
                    <label>{{ t('linkboard', 'Columns') }}</label>
                    <NcSelect v-model="form.columns" :options="columnOptions" :clearable="true" :placeholder="t('linkboard', 'Automatic')" />
                </div>

                <div class="category-editor__field">
                    <label>{{ t('linkboard', 'Parent category') }}</label>
                    <NcSelect v-model="selectedParent" :options="parentOptions" :clearable="true" :placeholder="t('linkboard', 'None (top-level)')" label="label" />
                </div>

                <NcCheckboxRadioSwitch :checked.sync="form.collapsed" type="switch">
                    {{ t('linkboard', 'Initially collapsed') }}
                </NcCheckboxRadioSwitch>
            </template>

            <template v-if="form.type === 'resources'">
                <NcCheckboxRadioSwitch :checked.sync="resourceConfig.showCpu" type="switch">
                    {{ t('linkboard', 'Show CPU usage') }}
                </NcCheckboxRadioSwitch>

                <NcCheckboxRadioSwitch :checked.sync="resourceConfig.showMemory" type="switch">
                    {{ t('linkboard', 'Show memory usage') }}
                </NcCheckboxRadioSwitch>

                <NcCheckboxRadioSwitch :checked.sync="resourceConfig.showUptime" type="switch">
                    {{ t('linkboard', 'Show uptime') }}
                </NcCheckboxRadioSwitch>

                <NcCheckboxRadioSwitch :checked.sync="resourceConfig.showCpuTemp" type="switch">
                    {{ t('linkboard', 'Show CPU temperature') }}
                </NcCheckboxRadioSwitch>

                <div v-if="resourceConfig.showCpuTemp" class="category-editor__field">
                    <label>{{ t('linkboard', 'Temperature unit') }}</label>
                    <NcSelect v-model="resourceConfig.tempUnit" :options="['C', 'F']" :clearable="false" />
                </div>

                <div class="category-editor__field">
                    <label>{{ t('linkboard', 'Disk paths (one per line)') }}</label>
                    <textarea v-model="resourceConfig.diskPathsText" class="category-editor__textarea" rows="3" placeholder="/"></textarea>
                </div>
            </template>
        </div>

        <div class="category-editor__actions">
            <NcButton type="primary" @click="save">
                {{ t('linkboard', 'Save') }}
            </NcButton>
        </div>
    </div>
</template>

<script>
import { t } from '@nextcloud/l10n'
import { NcButton, NcTextField, NcSelect, NcCheckboxRadioSwitch } from '@nextcloud/vue'
import { useDashboardStore } from '../../store/dashboard.js'
import CloseIcon from 'vue-material-design-icons/Close.vue'

export default {
    name: 'CategoryEditor',
    components: { NcButton, NcTextField, NcSelect, NcCheckboxRadioSwitch, CloseIcon },
    props: {
        category: { type: Object, required: true },
    },
    data() {
        var cfg = this.category.config || {}
        return {
            form: { ...this.category },
            columnOptions: [1, 2, 3, 4, 5, 6],
            typeOptions: [
                { id: 'default', label: t('linkboard', 'Default') },
                { id: 'spacer', label: t('linkboard', 'Spacer') },
                { id: 'resources', label: t('linkboard', 'Resources') },
            ],
            resourceConfig: {
                showCpu: cfg.showCpu !== undefined ? cfg.showCpu : true,
                showMemory: cfg.showMemory !== undefined ? cfg.showMemory : true,
                showUptime: cfg.showUptime !== undefined ? cfg.showUptime : true,
                showCpuTemp: cfg.showCpuTemp !== undefined ? cfg.showCpuTemp : false,
                tempUnit: cfg.tempUnit || 'C',
                diskPathsText: (cfg.diskPaths || ['/']).join('\n'),
            },
        }
    },
    computed: {
        parentOptions() {
            var store = useDashboardStore()
            var self = this
            var childIds = (this.category.children || []).map(function(c) { return c.id })
            return store.topLevelCategories
                .filter(function(c) { return c.id !== self.category.id && childIds.indexOf(c.id) === -1 })
                .map(function(c) { return { id: c.id, label: c.name } })
        },
        selectedParent: {
            get: function() {
                if (!this.form.parentId) return null
                return this.parentOptions.find(function(o) { return o.id === this.form.parentId }.bind(this)) || null
            },
            set: function(val) {
                this.form.parentId = val ? val.id : null
            },
        },
    },
    watch: {
        category: {
            handler(newVal) {
                this.form = { ...newVal }
                var cfg = newVal.config || {}
                this.resourceConfig = {
                    showCpu: cfg.showCpu !== undefined ? cfg.showCpu : true,
                    showMemory: cfg.showMemory !== undefined ? cfg.showMemory : true,
                    showUptime: cfg.showUptime !== undefined ? cfg.showUptime : true,
                    showCpuTemp: cfg.showCpuTemp !== undefined ? cfg.showCpuTemp : false,
                    tempUnit: cfg.tempUnit || 'C',
                    diskPathsText: (cfg.diskPaths || ['/']).join('\n'),
                }
            },
            deep: true,
        },
    },
    methods: {
        t,
        save() {
            var isDefault = this.form.type === 'default'
            var payload = {
                id: this.form.id,
                name: this.form.name,
                icon: isDefault ? this.form.icon : null,
                tab: isDefault ? this.form.tab : null,
                columns: isDefault ? this.form.columns : null,
                collapsed: isDefault ? this.form.collapsed : false,
                parentId: isDefault ? this.form.parentId : null,
                type: this.form.type || 'default',
            }
            if (this.form.type === 'resources') {
                var paths = this.resourceConfig.diskPathsText
                    .split('\n')
                    .map(function(p) { return p.trim() })
                    .filter(function(p) { return p !== '' })
                if (paths.length === 0) paths = ['/']
                payload.config = JSON.stringify({
                    showCpu: this.resourceConfig.showCpu,
                    showMemory: this.resourceConfig.showMemory,
                    showUptime: this.resourceConfig.showUptime,
                    showCpuTemp: this.resourceConfig.showCpuTemp,
                    tempUnit: this.resourceConfig.tempUnit,
                    diskPaths: paths,
                })
            }
            this.$emit('save', payload)
        },
    },
}
</script>

<style lang="scss" scoped>
.category-editor {
    position: fixed;
    top: 50px;
    right: 0;
    width: 360px;
    height: calc(100vh - 50px);
    background: var(--color-main-background);
    border-left: 1px solid var(--color-border);
    box-shadow: -4px 0 16px rgba(0, 0, 0, 0.1);
    display: flex;
    flex-direction: column;
    z-index: 1000;

    &__header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 16px 20px;
        border-bottom: 1px solid var(--color-border);
        h3 { margin: 0; font-size: 16px; font-weight: 600; }
    }

    &__form {
        flex: 1;
        padding: 20px;
        display: flex;
        flex-direction: column;
        gap: 16px;
    }

    &__field {
        display: flex;
        flex-direction: column;
        gap: 4px;
        label { font-size: 13px; font-weight: 500; color: var(--color-text-maxcontrast); }
    }

    &__textarea {
        width: 100%;
        padding: 8px;
        border: 2px solid var(--color-border-dark);
        border-radius: var(--border-radius);
        background: var(--color-main-background);
        color: var(--color-main-text);
        font-family: monospace;
        font-size: 13px;
        resize: vertical;
        &:focus {
            border-color: var(--color-primary-element);
            outline: none;
        }
    }

    &__actions {
        display: flex;
        justify-content: flex-end;
        padding: 16px 20px;
        border-top: 1px solid var(--color-border);
    }
}
</style>
