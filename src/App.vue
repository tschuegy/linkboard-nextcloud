<!--
LinkBoard - App.vue (Root Component)

SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
    <NcContent app-name="linkboard">
        <NcAppContent class="linkboard-content">
            <router-view />
        </NcAppContent>
    </NcContent>
</template>

<script>
import { NcContent, NcAppContent } from '@nextcloud/vue'

export default {
    name: 'App',
    components: {
        NcContent,
        NcAppContent,
    },
}
</script>

<style lang="scss">
// Fix scrolling - NC app content needs explicit overflow
.linkboard-content {
    overflow-y: auto !important;
    padding-bottom: 200px;
}
</style>
