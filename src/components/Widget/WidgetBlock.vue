<!--
LinkBoard - WidgetBlock.vue
Single statistics block: label (uppercase, small) + value (bold)

SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
    <div class="widget-block">
        <span class="widget-block__value" :title="String(value)">{{ value }}</span>
        <span class="widget-block__label">{{ label }}</span>
    </div>
</template>

<script>
export default {
    name: 'WidgetBlock',
    props: {
        label: { type: String, required: true },
        value: { type: [String, Number], required: true },
    },
}
</script>

<style lang="scss" scoped>
.widget-block {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 2px;
    padding: 6px 8px;
    min-width: 0;
    flex: 1 1 0;

    &__label {
        font-size: 10px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: var(--color-text-maxcontrast);
        white-space: normal;
        text-align: center;
        max-width: 100%;
    }

    &__value {
        font-size: 13px;
        font-weight: 700;
        color: var(--color-main-text);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 100%;
    }
}
</style>
