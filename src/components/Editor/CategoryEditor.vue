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
            <NcTextField v-model="form.icon" :label="t('linkboard', 'Icon')" placeholder="proxmox.png, https://..., oder mdi-server" />
            <NcTextField v-model="form.tab" :label="t('linkboard', 'Tab (optional)')" :placeholder="t('linkboard', 'Tab name for grouping')" />

            <div class="category-editor__field">
                <label>{{ t('linkboard', 'Columns') }}</label>
                <NcSelect v-model="form.columns" :options="columnOptions" :clearable="true" :placeholder="t('linkboard', 'Automatic')" />
            </div>

            <NcCheckboxRadioSwitch :checked.sync="form.collapsed" type="switch">
                {{ t('linkboard', 'Initially collapsed') }}
            </NcCheckboxRadioSwitch>
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
import CloseIcon from 'vue-material-design-icons/Close.vue'

export default {
    name: 'CategoryEditor',
    components: { NcButton, NcTextField, NcSelect, NcCheckboxRadioSwitch, CloseIcon },
    props: {
        category: { type: Object, required: true },
    },
    data() {
        return {
            form: { ...this.category },
            columnOptions: [1, 2, 3, 4, 5, 6],
        }
    },
    watch: {
        category: {
            handler(newVal) { this.form = { ...newVal } },
            deep: true,
        },
    },
    methods: {
        t,
        save() {
            this.$emit('save', {
                id: this.form.id,
                name: this.form.name,
                icon: this.form.icon,
                tab: this.form.tab,
                columns: this.form.columns,
                collapsed: this.form.collapsed,
            })
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

    &__actions {
        display: flex;
        justify-content: flex-end;
        padding: 16px 20px;
        border-top: 1px solid var(--color-border);
    }
}
</style>
