<!--
LinkBoard - SearchBar.vue
Quick search with keyboard shortcut hint

SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
    <div class="search-bar" :class="{ 'search-bar--focused': isFocused }">
        <MagnifyIcon :size="18" class="search-bar__icon" />
        <input
            ref="input"
            :value="value"
            type="text"
            class="search-bar__input"
            :placeholder="t('linkboard', 'Search...')"
            @input="$emit('input', $event.target.value)"
            @focus="isFocused = true"
            @blur="isFocused = false"
            @keydown.escape="handleEscape">
        <button
            v-if="value"
            class="search-bar__clear"
            :aria-label="t('linkboard', 'Clear search')"
            @click="$emit('input', '')">
            <CloseIcon :size="16" />
        </button>
    </div>
</template>

<script>
import { t } from '@nextcloud/l10n'
import MagnifyIcon from 'vue-material-design-icons/Magnify.vue'
import CloseIcon from 'vue-material-design-icons/Close.vue'

export default {
    name: 'SearchBar',
    components: { MagnifyIcon, CloseIcon },
    props: {
        value: { type: String, default: '' },
    },
    data: function() {
        return { isFocused: false }
    },
    methods: {
        t,
        handleEscape: function() {
            this.$emit('input', '')
            this.$refs.input.blur()
        },
    },
}
</script>

<style lang="scss" scoped>
.search-bar {
    display: flex; align-items: center;
    background: var(--color-background-dark);
    border: 1px solid var(--color-border);
    border-radius: 20px; padding: 4px 12px;
    width: 260px; transition: all 0.2s;

    &--focused {
        border-color: var(--color-primary);
        box-shadow: 0 0 0 2px var(--color-primary-element-light);
        width: 320px;
    }

    &__icon { color: var(--color-text-maxcontrast); flex-shrink: 0; }
    &__input {
        flex: 1; border: none; background: none;
        padding: 4px 8px; font-size: 14px;
        color: var(--color-main-text); outline: none;
    }
    &__clear {
        display: flex; align-items: center; justify-content: center;
        background: none; border: none; cursor: pointer;
        color: var(--color-text-maxcontrast); padding: 2px;
        &:hover { color: var(--color-main-text); }
    }
}
</style>
