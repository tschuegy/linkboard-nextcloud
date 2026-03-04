<!--
LinkBoard - EmptyState.vue
Shown when the dashboard has no categories yet

SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
    <div class="empty-state">
        <ViewDashboardIcon :size="64" class="empty-state__icon" />
        <h3>{{ t('linkboard', 'Welcome to LinkBoard!') }}</h3>
        <p>
            {{ t('linkboard', 'Create your first category to build your personal service dashboard.') }}
        </p>
        <NcButton type="primary" @click="$emit('create')">
            <template #icon>
                <PlusIcon :size="20" />
            </template>
            {{ t('linkboard', 'Create first category') }}
        </NcButton>
    </div>
</template>

<script>
import { t } from '@nextcloud/l10n'
import { NcButton } from '@nextcloud/vue'
import ViewDashboardIcon from 'vue-material-design-icons/ViewDashboard.vue'
import PlusIcon from 'vue-material-design-icons/Plus.vue'

export default {
    name: 'EmptyState',
    components: { NcButton, ViewDashboardIcon, PlusIcon },
    methods: { t },
}
</script>

<style lang="scss" scoped>
.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    padding: 80px 32px;
    color: var(--color-text-maxcontrast);

    &__icon {
        margin-bottom: 16px;
        opacity: 0.5;
    }

    h3 {
        font-size: 20px;
        font-weight: 600;
        color: var(--color-main-text);
        margin: 0 0 8px;
    }

    p {
        max-width: 400px;
        margin: 0 0 24px;
        line-height: 1.5;
    }
}
</style>
