<!--
LinkBoard - ServiceIcon.vue
Resolves and renders icons from URLs, local uploads, MDI names, or as avatar fallback

SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
    <div
        class="service-icon"
        :style="containerStyle">
        <!-- URL or local file icon -->
        <img
            v-if="resolvedUrl"
            :src="resolvedUrl"
            :alt="name"
            class="service-icon__img"
            @error="handleImgError">

        <!-- Avatar fallback -->
        <span
            v-else
            class="service-icon__fallback"
            :style="fallbackStyle">
            {{ fallbackLetter }}
        </span>
    </div>
</template>

<script>
import { iconApi } from '../../services/api.js'

export default {
    name: 'ServiceIcon',

    props: {
        icon: {
            type: String,
            default: null,
        },
        name: {
            type: String,
            default: '',
        },
        color: {
            type: String,
            default: null,
        },
        size: {
            type: Number,
            default: 40,
        },
    },

    data() {
        return {
            imgError: false,
            cdnFailed: false,
        }
    },

    computed: {
        containerStyle() {
            return {
                width: `${this.size}px`,
                height: `${this.size}px`,
            }
        },

        resolvedUrl() {
            if (!this.icon || this.imgError) return null

            // Full URL
            if (this.icon.startsWith('http://') || this.icon.startsWith('https://')) {
                return this.icon
            }

            // MDI icon (handled differently in the future - for now show fallback)
            if (this.icon.startsWith('mdi-') || this.icon.startsWith('si-')) {
                return null
            }

            // CDN first, local fallback
            if (!this.cdnFailed) {
                return this.dashboardIconUrl(this.icon)
            }
            return iconApi.getUrl(this.icon)
        },

        fallbackLetter() {
            // Use icon name for MDI
            if (this.icon && this.icon.startsWith('mdi-')) {
                return this.icon.replace('mdi-', '').charAt(0).toUpperCase()
            }
            return (this.name || '?').charAt(0).toUpperCase()
        },

        fallbackStyle() {
            const hue = this.stringToHue(this.name || 'default')
            const bg = this.color || `hsl(${hue}, 45%, 35%)`
            return {
                backgroundColor: bg,
                width: `${this.size}px`,
                height: `${this.size}px`,
                fontSize: `${this.size * 0.45}px`,
            }
        },
    },

    watch: {
        icon() {
            this.imgError = false
            this.cdnFailed = false
        },
    },

    methods: {
        dashboardIconUrl(icon) {
            const dot = icon.lastIndexOf('.')
            let name, ext
            if (dot > 0) {
                name = icon.substring(0, dot)
                ext = icon.substring(dot + 1)
            } else {
                name = icon
                ext = 'png'
            }
            return `https://cdn.jsdelivr.net/gh/homarr-labs/dashboard-icons/${ext}/${name}.${ext}`
        },

        handleImgError() {
            if (!this.cdnFailed && this.icon && !this.icon.startsWith('http://') && !this.icon.startsWith('https://')) {
                this.cdnFailed = true
            } else {
                this.imgError = true
            }
        },

        stringToHue(str) {
            let hash = 0
            for (let i = 0; i < str.length; i++) {
                hash = str.charCodeAt(i) + ((hash << 5) - hash)
            }
            return Math.abs(hash) % 360
        },
    },
}
</script>

<style lang="scss" scoped>
.service-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    border-radius: 8px;
    overflow: hidden;

    &__img {
        width: 100%;
        height: 100%;
        object-fit: contain;
        border-radius: 8px;
    }

    &__fallback {
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-weight: 700;
        border-radius: 8px;
    }
}
</style>
