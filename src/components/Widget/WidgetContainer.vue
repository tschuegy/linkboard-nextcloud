<!--
LinkBoard - WidgetContainer.vue
Container that arranges widget stat blocks horizontally

SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
    <div class="widget-container">
        <div v-if="error" class="widget-container__error">
            {{ error }}
        </div>
        <template v-else>
            <WidgetBlock
                v-for="(value, key) in data"
                :key="key"
                :label="fieldLabels[key] || key"
                :value="value" />
        </template>
    </div>
</template>

<script>
import WidgetBlock from './WidgetBlock.vue'

export default {
    name: 'WidgetContainer',
    components: { WidgetBlock },
    props: {
        data: { type: Object, default: () => ({}) },
        fieldLabels: { type: Object, default: () => ({}) },
        error: { type: String, default: null },
    },
}
</script>

<style lang="scss" scoped>
.widget-container {
    display: flex;
    align-items: stretch;
    gap: 4px;
    padding-top: 6px;
    border-top: 1px solid var(--color-border);
    margin-top: 8px;
    flex-wrap: nowrap;
    overflow: hidden;

    &__error {
        font-size: 11px;
        color: var(--color-error);
        padding: 2px 0;
    }
}
</style>
