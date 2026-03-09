<!--
LinkBoard - NotificationChannelEditor.vue
Modal dialog for creating/editing notification channels

SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
    <NcDialog :open="open" :name="isEdit ? t('linkboard', 'Edit notification channel') : t('linkboard', 'Add notification channel')" @update:open="$emit('close')">
        <form class="nc-editor" @submit.prevent="save">
            <NcTextField v-model="form.name" :label="t('linkboard', 'Channel name')" :placeholder="t('linkboard', 'Channel name')" />

            <div class="nc-editor__field">
                <label>{{ t('linkboard', 'Provider') }}</label>
                <NcSelect
                    v-model="selectedProvider"
                    :options="flatProviders"
                    :reduce="opt => opt.id"
                    label="label"
                    :clearable="false"
                    :disabled="isEdit"
                    :placeholder="t('linkboard', 'Provider')"
                    :selectable="opt => !opt.isHeader">
                    <template #option="opt">
                        <span v-if="opt.isHeader" class="nc-editor__provider-header">{{ opt.label }}</span>
                        <span v-else class="nc-editor__provider-item">{{ opt.label }}</span>
                    </template>
                </NcSelect>
            </div>

            <template v-if="configFields.length">
                <div v-for="field in configFields" v-show="isFieldVisible(field)" :key="field.key" class="nc-editor__field">
                    <template v-if="field.type === 'select'">
                        <label>{{ field.label }}</label>
                        <NcSelect
                            :value="getSelectValue(field)"
                            :options="field.options"
                            :reduce="opt => opt.value"
                            label="label"
                            :clearable="false"
                            :placeholder="field.placeholder || ''"
                            @input="form.config[field.key] = $event" />
                    </template>
                    <NcTextField
                        v-else-if="field.type !== 'textarea'"
                        v-model="form.config[field.key]"
                        :label="field.label"
                        :placeholder="field.placeholder || ''"
                        :type="field.type === 'password' ? 'password' : 'text'"
                        :autocomplete="field.type === 'password' ? 'off' : undefined" />
                    <template v-else>
                        <label>{{ field.label }}</label>
                        <textarea
                            v-model="form.config[field.key]"
                            :placeholder="field.placeholder || ''"
                            rows="3"
                            class="nc-editor__textarea" />
                    </template>
                </div>
            </template>

            <NcCheckboxRadioSwitch
                :checked="form.enabled"
                type="switch"
                @update:checked="form.enabled = $event">
                {{ t('linkboard', 'Enabled') }}
            </NcCheckboxRadioSwitch>
        </form>

        <template #actions>
            <NcButton type="tertiary" @click="$emit('close')">{{ t('linkboard', 'Cancel') }}</NcButton>
            <NcButton type="primary" @click="save">{{ t('linkboard', 'Save') }}</NcButton>
        </template>
    </NcDialog>
</template>

<script>
import { t } from '@nextcloud/l10n'
import { NcDialog, NcButton, NcTextField, NcSelect, NcCheckboxRadioSwitch } from '@nextcloud/vue'

var CATEGORY_LABELS = {
    universal: t('linkboard', 'Universal'),
    chat: t('linkboard', 'Chat platforms'),
    push: t('linkboard', 'Push services'),
    email: t('linkboard', 'E-Mail'),
}

export default {
    name: 'NotificationChannelEditor',
    components: { NcDialog, NcButton, NcTextField, NcSelect, NcCheckboxRadioSwitch },
    props: {
        open: { type: Boolean, default: false },
        channel: { type: Object, default: null },
        providers: { type: Array, default: function() { return [] } },
    },
    emits: ['close', 'save'],
    data() {
        return {
            form: {
                name: '',
                providerType: '',
                config: {},
                enabled: true,
            },
            selectedProvider: '',
        }
    },
    computed: {
        isEdit() {
            return this.channel !== null
        },
        flatProviders() {
            var groups = {}
            var order = []
            for (var i = 0; i < this.providers.length; i++) {
                var p = this.providers[i]
                var cat = CATEGORY_LABELS[p.category] || p.category
                if (!groups[cat]) {
                    groups[cat] = []
                    order.push(cat)
                }
                groups[cat].push({ id: p.id, label: p.label })
            }
            var result = []
            for (var j = 0; j < order.length; j++) {
                result.push({ id: '_cat_' + j, label: order[j], isHeader: true })
                result = result.concat(groups[order[j]])
            }
            return result
        },
        currentProvider() {
            if (!this.selectedProvider) return null
            for (var i = 0; i < this.providers.length; i++) {
                if (this.providers[i].id === this.selectedProvider) return this.providers[i]
            }
            return null
        },
        configFields() {
            return this.currentProvider ? this.currentProvider.configFields : []
        },
    },
    watch: {
        open: {
            handler(val) {
                if (val) {
                    if (this.channel) {
                        this.form = {
                            name: this.channel.name,
                            providerType: this.channel.providerType,
                            config: { ...this.channel.config },
                            enabled: this.channel.enabled,
                        }
                        this.selectedProvider = this.channel.providerType
                    } else {
                        this.form = { name: '', providerType: '', config: {}, enabled: true }
                        this.selectedProvider = ''
                    }
                }
            },
            immediate: true,
        },
        selectedProvider(val) {
            if (val && val !== this.form.providerType) {
                this.form.providerType = val
                if (!this.isEdit) {
                    this.form.config = {}
                }
            }
        },
    },
    methods: {
        t,
        isFieldVisible(field) {
            if (!field.showWhen) return true
            return this.form.config[field.showWhen[0]] === field.showWhen[1]
        },
        getSelectValue(field) {
            var val = this.form.config[field.key]
            if (!val && val !== 0 && val !== '0') return null
            for (var i = 0; i < field.options.length; i++) {
                if (field.options[i].value === val) return field.options[i].value
            }
            return val
        },
        save() {
            this.$emit('save', { ...this.form, config: JSON.stringify(this.form.config) })
        },
    },
}
</script>

<style lang="scss" scoped>
.nc-editor {
    display: flex;
    flex-direction: column;
    gap: 12px;
    padding: 8px 0;

    &__field {
        display: flex;
        flex-direction: column;
        gap: 4px;
        label { font-size: 13px; font-weight: 500; color: var(--color-text-maxcontrast); }
    }

    &__provider-header {
        font-weight: bold;
        color: var(--color-text-maxcontrast);
    }

    &__provider-item {
        padding-left: 12px;
    }

    &__textarea {
        width: 100%;
        padding: 8px;
        border: 1px solid var(--color-border);
        border-radius: var(--border-radius);
        background: var(--color-main-background);
        color: var(--color-main-text);
        font-family: monospace;
        font-size: 13px;
        resize: vertical;
    }
}
</style>
