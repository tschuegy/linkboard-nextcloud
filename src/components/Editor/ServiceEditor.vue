<!--
LinkBoard - ServiceEditor.vue
Sidebar panel for editing service properties

SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
    <div class="service-editor">
        <div class="service-editor__header">
            <h3>{{ t('linkboard', 'Edit service') }}</h3>
            <NcButton type="tertiary" :aria-label="t('linkboard', 'Close editor')" @click="$emit('close')">
                <template #icon>
                    <CloseIcon :size="20" />
                </template>
            </NcButton>
        </div>

        <div class="service-editor__form">
            <NcTextField v-model="form.name" :label="t('linkboard', 'Name')" />
            <NcTextField v-model="form.description" :label="t('linkboard', 'Description')" />
            <NcTextField v-model="form.href" :label="t('linkboard', 'URL')" />
            <NcTextField v-model="form.icon" :label="t('linkboard', 'Icon')" placeholder="proxmox.png, https://..., oder mdi-server" />
            <NcTextField v-model="form.iconColor" :label="t('linkboard', 'Icon color (optional)')" placeholder="#FF6600" />

            <div class="service-editor__field">
                <label>{{ t('linkboard', 'Link target') }}</label>
                <NcSelect v-model="form.target" :options="targetOptions" :clearable="false" />
            </div>

            <div class="service-editor__field">
                <label>{{ t('linkboard', 'Category') }}</label>
                <NcSelect v-model="form.categoryId" :options="categoryOptions" :clearable="false" label="label" :reduce="opt => opt.value" />
            </div>

            <div class="service-editor__section-title">{{ t('linkboard', 'Status Check') }}</div>
            <NcTextField v-model="form.pingUrl" :label="t('linkboard', 'Ping URL (optional)')" placeholder="https://service:8006" />
            <NcCheckboxRadioSwitch
                :checked="form.pingEnabled"
                type="switch"
                @update:checked="form.pingEnabled = $event">
                {{ t('linkboard', 'Status check enabled') }}
            </NcCheckboxRadioSwitch>

            <div class="service-editor__section-title">{{ t('linkboard', 'Widget') }}</div>
            <div class="service-editor__field">
                <label>{{ t('linkboard', 'Widget type') }}</label>
                <NcSelect
                    v-model="form.widgetType"
                    :options="widgetTypeOptions"
                    :clearable="false"
                    label="label"
                    :reduce="opt => opt.value" />
            </div>
            <template v-if="selectedWidgetDef">
                <template v-for="field in selectedWidgetDef.configFields">
                    <div v-if="field.type === 'mappings'" :key="field.key" class="service-editor__mappings">
                        <label class="service-editor__mappings-title">{{ t('linkboard', 'Field mappings') }}</label>
                        <div
                            v-for="(mapping, idx) in widgetConfigMappings"
                            :key="'m-' + idx"
                            class="service-editor__mapping-row">
                            <NcTextField
                                :value="mapping.field"
                                :label="t('linkboard', 'Field path')"
                                placeholder="path.to.key"
                                @update:value="updateMapping(idx, 'field', $event)" />
                            <NcTextField
                                :value="mapping.label"
                                :label="t('linkboard', 'Label')"
                                :placeholder="t('linkboard', 'Display name')"
                                @update:value="updateMapping(idx, 'label', $event)" />
                            <NcButton type="tertiary" :aria-label="t('linkboard', 'Remove mapping')" @click="removeMapping(idx)">
                                <template #icon>
                                    <DeleteIcon :size="20" />
                                </template>
                            </NcButton>
                        </div>
                        <NcButton type="secondary" @click="addMapping">
                            {{ t('linkboard', '+ Add mapping') }}
                        </NcButton>
                    </div>
                    <div v-else :key="field.key" class="service-editor__field">
                        <NcTextField
                            :value="widgetConfigValue(field.key)"
                            :label="field.label"
                            :type="field.type === 'password' ? 'password' : 'text'"
                            :placeholder="field.placeholder || ''"
                            @update:value="setWidgetConfigValue(field.key, $event)" />
                    </div>
                </template>
            </template>
        </div>

        <div class="service-editor__actions">
            <NcButton type="error" @click="$emit('delete', service.id)">
                <template #icon>
                    <DeleteIcon :size="20" />
                </template>
                {{ t('linkboard', 'Delete') }}
            </NcButton>
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
import DeleteIcon from 'vue-material-design-icons/Delete.vue'

export default {
    name: 'ServiceEditor',
    components: { NcButton, NcTextField, NcSelect, NcCheckboxRadioSwitch, CloseIcon, DeleteIcon },
    props: {
        service: { type: Object, required: true },
        categories: { type: Array, default: () => [] },
    },
    data() {
        return {
            form: {
                ...this.service,
                widgetConfig: this.service.widgetConfig ? { ...this.service.widgetConfig } : {},
            },
            targetOptions: ['_blank', '_self'],
        }
    },
    computed: {
        categoryOptions() {
            return this.categories.map(cat => ({ value: cat.id, label: cat.name }))
        },
        widgetCatalog() {
            return useDashboardStore().widgetCatalog
        },
        widgetTypeOptions() {
            var opts = [{ value: '', label: t('linkboard', 'No widget') }]
            for (var i = 0; i < this.widgetCatalog.length; i++) {
                var w = this.widgetCatalog[i]
                opts.push({ value: w.id, label: w.label })
            }
            return opts
        },
        selectedWidgetDef() {
            if (!this.form.widgetType) return null
            return this.widgetCatalog.find(function(w) { return w.id === this.form.widgetType }.bind(this)) || null
        },
        widgetConfigMappings() {
            return (this.form.widgetConfig && Array.isArray(this.form.widgetConfig.mappings))
                ? this.form.widgetConfig.mappings
                : []
        },
    },
    watch: {
        service: {
            handler(newVal) {
                this.form = {
                    ...newVal,
                    widgetConfig: newVal.widgetConfig ? { ...newVal.widgetConfig } : {},
                }
            },
            deep: true,
        },
        'form.pingUrl'(newVal) {
            if (newVal && newVal.trim()) {
                this.form.pingEnabled = true
            }
        },
        'form.widgetType'(newVal, oldVal) {
            if (newVal !== oldVal) {
                var cfg = {}
                var def = this.widgetCatalog.find(function(w) { return w.id === newVal })
                if (def) {
                    for (var i = 0; i < def.configFields.length; i++) {
                        if (def.configFields[i].type === 'mappings') {
                            cfg[def.configFields[i].key] = []
                        }
                    }
                }
                this.form.widgetConfig = cfg
            }
        },
    },
    methods: {
        t,
        widgetConfigValue(key) {
            return (this.form.widgetConfig && this.form.widgetConfig[key]) || ''
        },
        setWidgetConfigValue(key, value) {
            if (!this.form.widgetConfig) this.form.widgetConfig = {}
            this.$set(this.form, 'widgetConfig', { ...this.form.widgetConfig, [key]: value })
        },
        addMapping() {
            var mappings = this.widgetConfigMappings.slice()
            mappings.push({ field: '', label: '' })
            this.$set(this.form, 'widgetConfig', { ...this.form.widgetConfig, mappings: mappings })
        },
        removeMapping(idx) {
            var mappings = this.widgetConfigMappings.slice()
            mappings.splice(idx, 1)
            this.$set(this.form, 'widgetConfig', { ...this.form.widgetConfig, mappings: mappings })
        },
        updateMapping(idx, prop, value) {
            var mappings = this.widgetConfigMappings.map(function(m) { return { ...m } })
            mappings[idx][prop] = value
            this.$set(this.form, 'widgetConfig', { ...this.form.widgetConfig, mappings: mappings })
        },
        save() {
            var payload = {
                id: this.form.id,
                name: this.form.name,
                description: this.form.description,
                href: this.form.href,
                icon: this.form.icon,
                iconColor: this.form.iconColor,
                target: this.form.target,
                categoryId: this.form.categoryId,
                pingUrl: this.form.pingUrl || null,
                pingEnabled: !!this.form.pingEnabled,
            }
            if (this.form.widgetType) {
                payload.widgetType = this.form.widgetType
                payload.widgetConfig = this.form.widgetConfig || {}
            } else {
                payload.widgetType = ''
            }
            this.$emit('save', payload)
        },
    },
}
</script>

<style lang="scss" scoped>
.service-editor {
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
    overflow-y: auto;

    &__header {
        display: flex; align-items: center; justify-content: space-between;
        padding: 16px 20px; border-bottom: 1px solid var(--color-border);
        h3 { margin: 0; font-size: 16px; font-weight: 600; }
    }

    &__form {
        flex: 1; padding: 20px;
        display: flex; flex-direction: column; gap: 16px;
    }

    &__section-title {
        font-size: 13px; font-weight: 600; color: var(--color-text-maxcontrast);
        text-transform: uppercase; letter-spacing: 0.5px;
        padding-top: 8px; border-top: 1px solid var(--color-border);
    }

    &__field {
        display: flex; flex-direction: column; gap: 4px;
        label { font-size: 13px; font-weight: 500; color: var(--color-text-maxcontrast); }
    }

    &__mappings {
        display: flex; flex-direction: column; gap: 8px;
    }

    &__mappings-title {
        font-size: 13px; font-weight: 600; color: var(--color-text-maxcontrast);
    }

    &__mapping-row {
        display: flex; gap: 8px; align-items: flex-end;
        .input-field { flex: 1; }
    }

    &__actions {
        display: flex; justify-content: space-between;
        padding: 16px 20px; border-top: 1px solid var(--color-border);
    }
}
</style>
