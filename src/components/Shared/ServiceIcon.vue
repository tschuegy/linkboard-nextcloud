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

        <!-- MDI icon (inline SVG) -->
        <svg
            v-else-if="mdiPath"
            class="service-icon__mdi"
            :width="size"
            :height="size"
            viewBox="0 0 24 24">
            <path :d="mdiPath" fill="currentColor" />
        </svg>

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

var mdiModuleCache = null

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
            mdiPath: null,
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

            // MDI icons rendered via inline SVG (mdiPath)
            if (this.icon.startsWith('mdi-')) {
                return null
            }

            // CDN first, local fallback
            if (!this.cdnFailed) {
                return this.dashboardIconUrl(this.icon)
            }
            return iconApi.getUrl(this.icon)
        },

        fallbackLetter() {
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
            if (this.icon && this.icon.startsWith('mdi-')) {
                this.loadMdiIcon(this.icon)
            } else {
                this.mdiPath = null
            }
        },
    },

    mounted() {
        if (this.icon && this.icon.startsWith('mdi-')) {
            this.loadMdiIcon(this.icon)
        }
    },

    methods: {
        loadMdiIcon(iconName) {
            var self = this
            // Convert kebab-case to camelCase: mdi-server -> mdiServer
            var camel = iconName.replace(/-([a-z])/g, function(m, c) {
                return c.toUpperCase()
            })
            var promise = mdiModuleCache
                ? Promise.resolve(mdiModuleCache)
                : import('@mdi/js').then(function(mod) {
                    mdiModuleCache = mod
                    return mod
                })
            promise.then(function(mod) {
                if (self.icon === iconName) {
                    self.mdiPath = mod[camel] || null
                }
            })
        },

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

    &__mdi {
        color: var(--color-main-text);
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
