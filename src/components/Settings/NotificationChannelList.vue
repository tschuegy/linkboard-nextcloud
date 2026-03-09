<!--
LinkBoard - NotificationChannelList.vue
List of configured notification channels with add/edit/test/delete

SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
    <div class="nc-list">
        <NcButton @click="openEditor(null)">
            <template #icon><PlusIcon :size="20" /></template>
            {{ t('linkboard', 'Add notification channel') }}
        </NcButton>

        <div v-if="channels.length" class="nc-list__items">
            <div v-for="ch in channels" :key="ch.id" class="nc-list__item">
                <div class="nc-list__info">
                    <strong>{{ ch.name }}</strong>
                    <span class="nc-list__type">{{ getProviderLabel(ch.providerType) }}</span>
                </div>
                <div class="nc-list__actions">
                    <NcCheckboxRadioSwitch
                        :checked="ch.enabled"
                        type="switch"
                        @update:checked="toggleEnabled(ch, $event)" />
                    <NcButton type="tertiary" :aria-label="t('linkboard', 'Edit')" @click="openEditor(ch)">
                        <template #icon><PencilIcon :size="16" /></template>
                    </NcButton>
                    <NcButton
                        type="tertiary"
                        :aria-label="t('linkboard', 'Test')"
                        :disabled="testing === ch.id"
                        @click="testChannel(ch)">
                        <template #icon><PlayIcon :size="16" /></template>
                    </NcButton>
                    <NcButton type="tertiary" :aria-label="t('linkboard', 'Delete')" @click="deleteChannel(ch)">
                        <template #icon><DeleteIcon :size="16" /></template>
                    </NcButton>
                </div>
                <NcNoteCard v-if="testResults[ch.id] === true" type="success" class="nc-list__test-result">
                    {{ t('linkboard', 'Test successful') }}
                </NcNoteCard>
                <NcNoteCard v-else-if="testResults[ch.id]" type="error" class="nc-list__test-result">
                    {{ t('linkboard', 'Test failed: %s').replace('%s', testResults[ch.id]) }}
                </NcNoteCard>
            </div>
        </div>

        <NotificationChannelEditor
            :open="editorOpen"
            :channel="editingChannel"
            :providers="providers"
            @close="editorOpen = false"
            @save="handleSave" />
    </div>
</template>

<script>
import { t } from '@nextcloud/l10n'
import { NcButton, NcCheckboxRadioSwitch, NcNoteCard } from '@nextcloud/vue'
import { notificationChannelApi } from '../../services/api.js'
import NotificationChannelEditor from './NotificationChannelEditor.vue'
import PlusIcon from 'vue-material-design-icons/Plus.vue'
import PencilIcon from 'vue-material-design-icons/Pencil.vue'
import PlayIcon from 'vue-material-design-icons/Play.vue'
import DeleteIcon from 'vue-material-design-icons/Delete.vue'

export default {
    name: 'NotificationChannelList',
    components: {
        NcButton, NcCheckboxRadioSwitch, NcNoteCard,
        NotificationChannelEditor,
        PlusIcon, PencilIcon, PlayIcon, DeleteIcon,
    },
    data() {
        return {
            channels: [],
            providers: [],
            editorOpen: false,
            editingChannel: null,
            testing: null,
            testResults: {},
        }
    },
    async mounted() {
        await this.loadData()
    },
    methods: {
        t,
        async loadData() {
            try {
                var results = await Promise.all([
                    notificationChannelApi.getAll(),
                    notificationChannelApi.getProviders(),
                ])
                this.channels = results[0].data
                this.providers = results[1].data
            } catch (err) {
                console.error('Failed to load notification channels', err)
            }
        },
        getProviderLabel(type) {
            for (var i = 0; i < this.providers.length; i++) {
                if (this.providers[i].id === type) return this.providers[i].label
            }
            return type
        },
        openEditor(channel) {
            this.editingChannel = channel
            this.editorOpen = true
        },
        async handleSave(data) {
            try {
                if (this.editingChannel) {
                    await notificationChannelApi.update(this.editingChannel.id, data)
                } else {
                    await notificationChannelApi.create(data)
                }
                this.editorOpen = false
                await this.loadData()
            } catch (err) {
                console.error('Failed to save notification channel', err)
            }
        },
        async toggleEnabled(ch, enabled) {
            try {
                await notificationChannelApi.update(ch.id, { enabled })
                ch.enabled = enabled
            } catch (err) {
                console.error('Failed to toggle channel', err)
            }
        },
        async testChannel(ch) {
            this.testing = ch.id
            this.$set(this.testResults, ch.id, undefined)
            try {
                var response = await notificationChannelApi.test(ch.id)
                this.$set(this.testResults, ch.id, response.data.success ? true : response.data.error)
            } catch (err) {
                var msg = err.response?.data?.error || err.message
                this.$set(this.testResults, ch.id, msg)
            }
            this.testing = null
        },
        async deleteChannel(ch) {
            if (confirm(t('linkboard', 'Really delete notification channel "%1$s"?').replace('%1$s', ch.name))) {
                try {
                    await notificationChannelApi.delete(ch.id)
                    await this.loadData()
                } catch (err) {
                    console.error('Failed to delete channel', err)
                }
            }
        },
    },
}
</script>

<style lang="scss" scoped>
.nc-list {
    &__items {
        display: flex;
        flex-direction: column;
        gap: 4px;
        margin-top: 12px;
    }

    &__item {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 8px;
        padding: 8px 12px;
        background: var(--color-background-dark);
        border-radius: 8px;
    }

    &__info {
        flex: 1;
        min-width: 0;
        strong { display: block; font-size: 14px; }
    }

    &__type {
        font-size: 12px;
        color: var(--color-text-maxcontrast);
    }

    &__actions {
        display: flex;
        align-items: center;
        gap: 2px;
    }

    &__test-result {
        width: 100%;
        margin-top: 4px;
    }
}
</style>
