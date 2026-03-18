<!--
LinkBoard - SettingsPage.vue
App settings page with Import/Export

SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
    <div class="settings-page">
        <div class="settings-page__header">
            <NcButton type="tertiary" :aria-label="t('linkboard', 'Back to LinkBoard')" @click="$router.push('/')">
                <template #icon>
                    <ArrowLeftIcon :size="20" />
                </template>
                {{ t('linkboard', 'Back to LinkBoard') }}
            </NcButton>
            <h2>{{ t('linkboard', 'Settings') }}</h2>
        </div>

        <div class="settings-page__section">
            <h3>{{ t('linkboard', 'General') }}</h3>
            <NcTextField v-model="form.title" :label="t('linkboard', 'LinkBoard title')" />

        <div class="settings-page__section">
            <h3>{{ t('linkboard', 'Font colors') }}</h3>
            <NcCheckboxRadioSwitch
                :checked="effectiveFontColorMode === 'auto'"
                type="switch"
                @update:checked="form.font_color_mode = $event ? 'auto' : 'manual'">
                {{ t('linkboard', 'Automatic (detect from background)') }}
            </NcCheckboxRadioSwitch>
            <div :style="effectiveFontColorMode === 'auto' ? { opacity: 0.4, pointerEvents: 'none' } : {}">
                <div class="settings-page__field">
                    <label>{{ t('linkboard', 'Title font color') }}</label>
                    <NcColorPicker v-model="form.manual_color_title">
                        <NcButton> <template #icon><span class="color-swatch" :style="{ background: form.manual_color_title || '#000' }" /></template> {{ form.manual_color_title || t('linkboard', 'Choose color') }} </NcButton>
                    </NcColorPicker>
                </div>
                <div class="settings-page__field">
                    <label>{{ t('linkboard', 'Category font color') }}</label>
                    <NcColorPicker v-model="form.manual_color_category">
                        <NcButton> <template #icon><span class="color-swatch" :style="{ background: form.manual_color_category || '#000' }" /></template> {{ form.manual_color_category || t('linkboard', 'Choose color') }} </NcButton>
                    </NcColorPicker>
                </div>
                <div class="settings-page__field">
                    <label>{{ t('linkboard', 'Service font color') }}</label>
                    <NcColorPicker v-model="form.manual_color_service">
                        <NcButton> <template #icon><span class="color-swatch" :style="{ background: form.manual_color_service || '#000' }" /></template> {{ form.manual_color_service || t('linkboard', 'Choose color') }} </NcButton>
                    </NcColorPicker>
                </div>
                <div class="settings-page__field">
                    <label>{{ t('linkboard', 'Description font color') }}</label>
                    <NcColorPicker v-model="form.manual_color_description">
                        <NcButton> <template #icon><span class="color-swatch" :style="{ background: form.manual_color_description || '#000' }" /></template> {{ form.manual_color_description || t('linkboard', 'Choose color') }} </NcButton>
                    </NcColorPicker>
                </div>
                <div class="settings-page__field">
                    <label>{{ t('linkboard', 'Widget value font color') }}</label>
                    <NcColorPicker v-model="form.manual_color_widget_value">
                        <NcButton> <template #icon><span class="color-swatch" :style="{ background: form.manual_color_widget_value || '#000' }" /></template> {{ form.manual_color_widget_value || t('linkboard', 'Choose color') }} </NcButton>
                    </NcColorPicker>
                </div>
                <div class="settings-page__field">
                    <label>{{ t('linkboard', 'Widget label font color') }}</label>
                    <NcColorPicker v-model="form.manual_color_widget_label">
                        <NcButton> <template #icon><span class="color-swatch" :style="{ background: form.manual_color_widget_label || '#000' }" /></template> {{ form.manual_color_widget_label || t('linkboard', 'Choose color') }} </NcButton>
                    </NcColorPicker>
                </div>
                <div class="settings-page__field">
                    <label>{{ t('linkboard', 'Card background color') }}</label>
                    <NcColorPicker v-model="form.manual_color_card_bg">
                        <NcButton> <template #icon><span class="color-swatch" :style="{ background: form.manual_color_card_bg || '#000' }" /></template> {{ form.manual_color_card_bg || t('linkboard', 'Choose color') }} </NcButton>
                    </NcColorPicker>
                </div>
                <div class="settings-page__field">
                    <label>{{ t('linkboard', 'Header button font color') }}</label>
                    <NcColorPicker v-model="form.manual_color_header_button">
                        <NcButton> <template #icon><span class="color-swatch" :style="{ background: form.manual_color_header_button || '#000' }" /></template> {{ form.manual_color_header_button || t('linkboard', 'Choose color') }} </NcButton>
                    </NcColorPicker>
                </div>
            </div>
        </div>
            <NcTextField v-model="form.background_url" :label="t('linkboard', 'Background image URL (optional)')" placeholder="https://..." />
            <div class="settings-page__field">
                <label>{{ t('linkboard', 'Background blur') }}</label>
                <NcSelect v-model="form.background_blur" :options="blurOptions" :clearable="false" />
            </div>
        </div>

        <div class="settings-page__section">
            <h3>{{ t('linkboard', 'Layout') }}</h3>
            <div class="settings-page__field">
                <label>{{ t('linkboard', 'Max. columns') }}</label>
                <NcSelect v-model="form.max_columns" :options="columnOptions" :clearable="false" />
            </div>
            <div class="settings-page__field">
                <label>{{ t('linkboard', 'Card style') }}</label>
                <NcSelect v-model="form.card_style" :options="cardStyleOptions" :clearable="false" />
            </div>
            <div class="settings-page__field">
                <label>{{ t('linkboard', 'Card background') }}</label>
                <NcSelect
                    v-model="form.card_background"
                    :options="cardBackgroundOptions"
                    :reduce="opt => opt.id"
                    label="label"
                    :clearable="false" />
            </div>
            <div class="settings-page__field">
                <label>{{ t('linkboard', 'Status display') }}</label>
                <NcSelect v-model="form.status_style" :options="statusStyleOptions" :clearable="false" />
            </div>
            <NcCheckboxRadioSwitch
                :checked="form.show_status_bars === 'true'"
                type="switch"
                @update:checked="form.show_status_bars = $event ? 'true' : 'false'">
                {{ t('linkboard', 'Show status history bars on cards') }}
            </NcCheckboxRadioSwitch>
            <div v-if="form.show_status_bars === 'true'" class="settings-page__field">
                <label>{{ t('linkboard', 'Status bar opacity') }}</label>
                <div class="settings-page__range-row">
                    <input type="range" min="20" max="100" step="1"
                        :value="opacityPercent"
                        @input="form.status_bars_opacity = ($event.target.value / 100).toFixed(2)" />
                    <span>{{ opacityPercent }}%</span>
                </div>
            </div>
            <div class="settings-page__field">
                <label>{{ t('linkboard', 'Spacer style') }}</label>
                <NcSelect
                    v-model="form.spacer_style"
                    :options="spacerStyleOptions"
                    :reduce="opt => opt.id"
                    label="label"
                    :clearable="false">
                    <template #option="opt">
                        <div class="spacer-option">
                            <span class="spacer-option__label">{{ opt.label }}</span>
                            <span class="spacer-option__preview" v-html="getSpacerPreview(opt.id)" />
                        </div>
                    </template>
                    <template #selected-option="opt">
                        <div class="spacer-option">
                            <span class="spacer-option__label">{{ opt.label }}</span>
                            <span class="spacer-option__preview" v-html="getSpacerPreview(opt.id)" />
                        </div>
                    </template>
                </NcSelect>
            </div>
            <NcCheckboxRadioSwitch
                :checked="form.show_search === 'true'"
                type="switch"
                @update:checked="form.show_search = $event ? 'true' : 'false'">
                {{ t('linkboard', 'Show search bar') }}
            </NcCheckboxRadioSwitch>
            <NcCheckboxRadioSwitch
                :checked="form.show_category_count === 'true'"
                type="switch"
                @update:checked="form.show_category_count = $event ? 'true' : 'false'">
                {{ t('linkboard', 'Show service count per category') }}
            </NcCheckboxRadioSwitch>
            <NcCheckboxRadioSwitch
                :checked="form.check_for_updates === 'true'"
                type="switch"
                @update:checked="form.check_for_updates = $event ? 'true' : 'false'">
                {{ t('linkboard', 'Check for updates') }}
            </NcCheckboxRadioSwitch>
        </div>

        <div class="settings-page__section">
            <h3>{{ t('linkboard', 'Notifications') }}</h3>
            <NcCheckboxRadioSwitch
                :checked="form.notify_nextcloud === 'true'"
                type="switch"
                @update:checked="form.notify_nextcloud = $event ? 'true' : 'false'">
                {{ t('linkboard', 'Nextcloud notifications') }}
            </NcCheckboxRadioSwitch>
            <div class="settings-page__field">
                <label>{{ t('linkboard', 'Status check timeout (per check)') }}</label>
                <NcSelect
                    v-model="form.status_check_timeout"
                    :options="timeoutOptions"
                    :reduce="opt => opt.id"
                    label="label"
                    :clearable="false" />
            </div>
            <NcCheckboxRadioSwitch
                :checked="form.status_checks_parallel === 'true'"
                type="switch"
                @update:checked="form.status_checks_parallel = $event ? 'true' : 'false'">
                {{ t('linkboard', 'Run status checks in parallel') }}
            </NcCheckboxRadioSwitch>
            <div class="settings-page__field">
                <label>{{ t('linkboard', 'Offline alert after consecutive failed checks') }}</label>
                <NcSelect
                    v-model="form.notify_failures_threshold"
                    :options="thresholdOptions"
                    :clearable="false" />
            </div>
            <NcCheckboxRadioSwitch
                :checked="form.notify_recovery === 'true'"
                type="switch"
                @update:checked="form.notify_recovery = $event ? 'true' : 'false'">
                {{ t('linkboard', 'Notify when service comes back online') }}
            </NcCheckboxRadioSwitch>
            <NotificationChannelList />
        </div>

        <div v-if="isAdmin" class="settings-page__section">
            <h3>{{ t('linkboard', 'Administration') }}</h3>
            <p class="settings-page__hint">
                {{ t('linkboard', 'These settings apply to all users.') }}
            </p>
            <div class="settings-page__field">
                <label>{{ t('linkboard', 'Status check interval') }}</label>
                <div class="settings-page__range-row">
                    <input type="range" min="1" max="30" step="1"
                        :value="adminForm.status_check_interval_min"
                        @input="adminForm.status_check_interval_min = parseInt($event.target.value)" />
                    <span>{{ t('linkboard', '{n} minutes', { n: adminForm.status_check_interval_min }) }}</span>
                </div>
            </div>
            <NcButton type="primary" @click="saveAdminSettings">
                {{ t('linkboard', 'Save admin settings') }}
            </NcButton>
        </div>

        <div class="settings-page__section">
            <h3>{{ t('linkboard', 'Icons') }}</h3>
            <p class="settings-page__hint">
                {{ t('linkboard', 'Upload icons and reference them by filename in services (e.g. proxmox.png).') }}
            </p>
            <div class="settings-page__upload">
                <input ref="fileInput" type="file" accept="image/png,image/jpeg,image/svg+xml,image/webp" hidden @change="handleUpload">
                <NcButton @click="$refs.fileInput.click()">
                    <template #icon><UploadIcon :size="20" /></template>
                    {{ t('linkboard', 'Upload icon') }}
                </NcButton>
            </div>
            <div v-if="icons.length" class="settings-page__icons">
                <div v-for="icon in icons" :key="icon.name" class="settings-page__icon-item">
                    <img :src="getIconUrl(icon.name)" :alt="icon.name" class="settings-page__icon-preview">
                    <span>{{ icon.name }}</span>
                    <NcButton type="tertiary" :aria-label="t('linkboard', 'Delete icon {name}', { name: icon.name })" @click="deleteIcon(icon.name)">
                        <template #icon><DeleteIcon :size="16" /></template>
                    </NcButton>
                </div>
            </div>
        </div>

        <!-- Import/Export Section -->
        <div class="settings-page__section">
            <h3>{{ t('linkboard', 'Import / Export') }}</h3>
            <p class="settings-page__hint">
                {{ t('linkboard', 'Export your dashboard as JSON or YAML, or import from a file. Gethomepage services.yaml is also supported.') }}
            </p>

            <div class="settings-page__ie-actions">
                <NcButton @click="exportJson">
                    <template #icon><DownloadIcon :size="20" /></template>
                    {{ t('linkboard', 'JSON Export') }}
                </NcButton>
                <NcButton @click="exportYaml">
                    <template #icon><DownloadIcon :size="20" /></template>
                    {{ t('linkboard', 'YAML Export') }}
                </NcButton>
            </div>

            <div class="settings-page__ie-import">
                <input ref="importInput" type="file" accept=".json,.yaml,.yml" hidden @change="handleImportFile">
                <NcButton @click="$refs.importInput.click()">
                    <template #icon><UploadIcon :size="20" /></template>
                    {{ t('linkboard', 'Import file') }}
                </NcButton>

                <div class="settings-page__ie-mode">
                    <NcCheckboxRadioSwitch
                        :checked="importMode === 'merge'"
                        type="switch"
                        @update:checked="importMode = $event ? 'merge' : 'replace'">
                        {{ t('linkboard', 'Add to existing dashboard (instead of replacing)') }}
                    </NcCheckboxRadioSwitch>
                </div>
            </div>

            <NcNoteCard v-if="importResult" type="success" @close="importResult = null">
                {{ t('linkboard', 'Import successful: {categories} categories, {services} services imported.', { categories: importResult.stats.categories, services: importResult.stats.services }) }}
            </NcNoteCard>
            <NcNoteCard v-if="importError" type="error" @close="importError = null">
                {{ importError }}
            </NcNoteCard>
        </div>

        <div class="settings-page__footer">
            <NcButton type="primary" @click="saveSettings">
                {{ t('linkboard', 'Save settings') }}
            </NcButton>
        </div>
    </div>
</template>

<script>
import { t } from '@nextcloud/l10n'
import { NcButton, NcTextField, NcSelect, NcCheckboxRadioSwitch, NcNoteCard, NcColorPicker } from '@nextcloud/vue'
import { mapState, mapActions } from 'pinia'
import { useDashboardStore } from '../../store/dashboard.js'
import { iconApi, importExportApi } from '../../services/api.js'
import { SPACER_STYLES, SPACER_LABELS, SPACER_CHARS, isUnicodeStyle } from '../../utils/spacerStyles.js'
import NotificationChannelList from './NotificationChannelList.vue'
import ArrowLeftIcon from 'vue-material-design-icons/ArrowLeft.vue'
import UploadIcon from 'vue-material-design-icons/Upload.vue'
import DownloadIcon from 'vue-material-design-icons/Download.vue'
import DeleteIcon from 'vue-material-design-icons/Delete.vue'

export default {
    name: 'SettingsPage',
    components: {
        NcButton, NcTextField, NcSelect, NcCheckboxRadioSwitch, NcNoteCard, NcColorPicker,
        NotificationChannelList, ArrowLeftIcon, UploadIcon, DownloadIcon, DeleteIcon,
    },
    data() {
        return {
            form: {},
            icons: [],
            columnOptions: ['2', '3', '4', '5', '6'],
            cardStyleOptions: ['default', 'compact', 'minimal'],
            statusStyleOptions: ['dot', 'basic'],
            cardBackgroundOptions: [
                { id: 'glass', label: t('linkboard', 'Glass') },
                { id: 'solid', label: t('linkboard', 'Solid (opaque)') },
                { id: 'flat', label: t('linkboard', 'Flat') },
                { id: 'transparent', label: t('linkboard', 'Transparent') },
            ],
            timeoutOptions: [
                { id: '100', label: '100 ms' },
                { id: '200', label: '200 ms' },
                { id: '500', label: '500 ms' },
                { id: '1000', label: '1 s' },
                { id: '2000', label: '2 s' },
                { id: '5000', label: '5 s' },
                { id: '10000', label: '10 s' },
            ],
            thresholdOptions: ['1', '2', '3', '5', '10'],
            blurOptions: ['none', 'sm', 'md', 'lg', 'xl'],
            spacerStyleOptions: SPACER_STYLES.map(function(s) {
                return { id: s.id, label: t('linkboard', SPACER_LABELS[s.id]) }
            }),
            adminForm: {
                status_check_interval_min: 5,
            },
            importMode: 'replace',
            importResult: null,
            importError: null,
        }
    },
    computed: {
        ...mapState(useDashboardStore, ['settings', 'isAdmin', 'adminSettings']),
        effectiveFontColorMode() {
            if (this.form.font_color_mode) return this.form.font_color_mode
            if (this.form.theme === 'manual') return 'manual'
            return 'auto'
        },
        opacityPercent() {
            return Math.round(parseFloat(this.form.status_bars_opacity || '0.8') * 100)
        },
    },
    watch: {
        settings: {
            handler(newVal) { this.form = { ...newVal } },
            immediate: true,
            deep: true,
        },
        adminSettings: {
            handler(newVal) {
                this.adminForm.status_check_interval_min = Math.round((newVal.status_check_interval || 300) / 60)
            },
            immediate: true,
            deep: true,
        },
    },
    async mounted() {
        const store = useDashboardStore()
        if (!store.categories.length) { await store.fetchDashboard() }
        this.form = { ...store.settings }
        this.loadIcons()
    },
    methods: {
        t,
        ...mapActions(useDashboardStore, ['updateSettings', 'updateAdminSettings', 'importData']),

        async saveSettings() { await this.updateSettings(this.form) },

        async saveAdminSettings() {
            await this.updateAdminSettings({
                statusCheckInterval: this.adminForm.status_check_interval_min * 60,
            })
            OC.Notification.showTemporary(t('linkboard', 'Admin settings saved'))
        },

        async loadIcons() {
            try { const { data } = await iconApi.getAll(); this.icons = data }
            catch { this.icons = [] }
        },
        getIconUrl(name) { return iconApi.getUrl(name) },
        async handleUpload(event) {
            const file = event.target.files[0]
            if (!file) return
            const formData = new FormData()
            formData.append('icon', file)
            try { await iconApi.upload(formData); await this.loadIcons() }
            catch (err) { console.error('Icon upload failed', err) }
            this.$refs.fileInput.value = ''
        },
        async deleteIcon(name) {
            if (confirm(t('linkboard', 'Really delete icon "{name}"?', { name }))) {
                await iconApi.delete(name)
                await this.loadIcons()
            }
        },

        getSpacerPreview(id) {
            if (isUnicodeStyle(id)) {
                var ch = SPACER_CHARS[id]
                if (id === 'fade') {
                    return '<span style="letter-spacing:0;opacity:0.6">' + ch + ch + ch + '</span>'
                }
                var sep = (id === 'dots' || id === 'stars' || id === 'diamonds' || id === 'arrows') ? ' ' : ''
                var text = ''
                for (var i = 0; i < 12; i++) {
                    text += (i > 0 ? sep : '') + ch
                }
                return '<span style="opacity:0.6">' + text + '</span>'
            }
            return '<span style="display:inline-block;width:80px;border-top:2px ' + id + ' var(--color-border);vertical-align:middle"></span>'
        },

        // ── Import/Export ────────────────────────────
        exportJson() { window.open(importExportApi.exportJsonUrl(), '_blank') },
        exportYaml() { window.open(importExportApi.exportYamlUrl(), '_blank') },

        async handleImportFile(event) {
            const file = event.target.files[0]
            if (!file) return

            this.importResult = null
            this.importError = null

            try {
                const text = await file.text()
                let data

                if (file.name.endsWith('.json')) {
                    data = JSON.parse(text)
                } else if (file.name.endsWith('.yaml') || file.name.endsWith('.yml')) {
                    data = this.parseYaml(text)
                } else {
                    this.importError = t('linkboard', 'Unknown file format. Please use JSON or YAML.')
                    return
                }

                console.log('LinkBoard: Parsed import data', JSON.stringify(data).substring(0, 500))
                const result = await this.importData(data, this.importMode)
                this.importResult = result
            } catch (err) {
                console.error('LinkBoard: Import error', err)
                this.importError = err.response?.data?.error || err.message || t('linkboard', 'Import failed')
            }

            this.$refs.importInput.value = ''
        },

        /**
         * Detect format and parse YAML.
         * Supports: Gethomepage services.yaml + LinkBoard YAML export
         */
        parseYaml(text) {
            // Check if it's LinkBoard format (has top-level 'categories:' or 'settings:')
            if (/^(categories|settings|version):/m.test(text)) {
                return this.parseLinkboardYaml(text)
            }
            return this.parseGethomepageYaml(text)
        },

        /**
         * Parse Gethomepage services.yaml format:
         *
         * ---
         * - CategoryName:
         *     - ServiceName:
         *         href: https://...
         *         description: Some text
         *         icon: icon.png
         *         widget:
         *           type: customapi
         *           ...
         */
        parseGethomepageYaml(text) {
            const lines = text.split('\n')
            const categories = []
            let currentCategory = null
            let currentService = null
            let skipNestedDepth = -1  // Track nested objects to skip (e.g. widget)

            for (const rawLine of lines) {
                const line = rawLine.replace(/\r$/, '').replace(/\t/g, '    ')
                // Completely empty or document separator
                if (line.trim() === '' || line.trim() === '---') continue
                // Full-line comment
                if (line.trim().startsWith('#')) continue

                const indent = line.search(/\S/)
                const trimmed = line.trim()

                // If we're skipping a nested block, check indent
                if (skipNestedDepth >= 0) {
                    if (indent > skipNestedDepth) continue  // Still in nested block
                    skipNestedDepth = -1  // Exited nested block
                }

                // Category: "- CategoryName:" at indent 0
                // Pattern: starts with "- ", the rest (after trim) ends with ":"
                if (indent === 0 && trimmed.startsWith('- ')) {
                    const rest = trimmed.slice(2)
                    // Remove trailing ":" to get category name
                    if (rest.endsWith(':')) {
                        const catName = rest.slice(0, -1).trim()
                        if (catName) {
                            currentCategory = { name: catName, services: [] }
                            categories.push(currentCategory)
                            currentService = null
                        }
                    }
                    continue
                }

                // Service: "    - ServiceName:" at indent 4
                if (indent === 4 && trimmed.startsWith('- ') && currentCategory) {
                    const rest = trimmed.slice(2)
                    if (rest.endsWith(':')) {
                        const svcName = rest.slice(0, -1).trim()
                        if (svcName) {
                            currentService = { name: svcName }
                            currentCategory.services.push(currentService)
                        }
                    }
                    continue
                }

                // Service property: "        key: value" at indent 8
                if (indent === 8 && currentService) {
                    const colonIdx = trimmed.indexOf(':')
                    if (colonIdx > 0) {
                        const key = trimmed.substring(0, colonIdx).trim()
                        const rawVal = trimmed.substring(colonIdx + 1).trim()

                        // Skip commented-out properties
                        if (key.startsWith('#')) continue

                        // If value is empty, this is a nested object (e.g. "widget:")
                        // → skip all children
                        if (!rawVal) {
                            skipNestedDepth = indent
                            continue
                        }

                        // Strip inline comments, but be careful with URLs containing #
                        const val = this.stripInlineComment(rawVal)

                        // Map gethomepage keys → linkboard keys
                        const keyMap = {
                            url: 'href',
                            ping: 'pingUrl',
                            siteMonitor: 'pingUrl',
                        }
                        const mappedKey = keyMap[key] || key
                        currentService[mappedKey] = this.yamlVal(val)
                    }
                    continue
                }
            }

            if (categories.length === 0) {
                throw new Error(t('linkboard', 'No categories found in YAML file.'))
            }

            console.log(`LinkBoard: Parsed ${categories.length} categories from Gethomepage YAML`)
            return { categories }
        },

        /**
         * Strip inline comments from a YAML value.
         * Tricky because URLs contain # (e.g. https://example.com/#/page)
         * Rule: # preceded by whitespace is a comment, unless inside quotes or a URL
         */
        stripInlineComment(val) {
            // If the value is quoted, return as-is (minus quotes)
            if ((val.startsWith('"') && val.endsWith('"')) ||
                (val.startsWith("'") && val.endsWith("'"))) {
                return val.slice(1, -1)
            }

            // Find " #" pattern (space + hash) that's likely a comment
            // But not inside URLs - a URL won't have " # " typically
            const commentMatch = val.match(/\s+#\s+/)
            if (commentMatch) {
                return val.substring(0, commentMatch.index).trim()
            }

            return val
        },

        /**
         * Parse LinkBoard's own YAML export format
         */
        parseLinkboardYaml(text) {
            const lines = text.split('\n')
            const result = {}
            let section = null
            let currentCat = null
            let currentSvc = null
            const categories = []

            for (const rawLine of lines) {
                const line = rawLine.replace(/\r$/, '')
                if (line.trim() === '' || line.trim().startsWith('#') || line.trim() === '---') continue

                const indent = line.search(/\S/)
                const trimmed = line.trim()

                if (indent === 0 && trimmed.includes(':')) {
                    const [key, ...rest] = trimmed.split(':')
                    const val = rest.join(':').trim()
                    if (key === 'settings') { section = 'settings'; result.settings = {} }
                    else if (key === 'categories') { section = 'categories' }
                    else if (val) { result[key] = this.yamlVal(val) }
                    continue
                }

                if (section === 'settings' && indent >= 2) {
                    const m = trimmed.match(/^([\w_]+)\s*:\s*(.*)$/)
                    if (m) result.settings[m[1]] = this.yamlVal(m[2])
                    continue
                }

                if (section === 'categories') {
                    if (indent === 2 && trimmed.startsWith('- name:')) {
                        currentCat = { name: this.yamlVal(trimmed.replace('- name:', '').trim()), services: [] }
                        categories.push(currentCat)
                        currentSvc = null
                        continue
                    }
                    if (indent === 4 && currentCat && !trimmed.startsWith('-')) {
                        if (trimmed === 'services:') continue
                        const m = trimmed.match(/^([\w_]+)\s*:\s*(.*)$/)
                        if (m) currentCat[m[1]] = this.yamlVal(m[2])
                        continue
                    }
                    if (indent === 6 && trimmed.startsWith('- name:') && currentCat) {
                        currentSvc = { name: this.yamlVal(trimmed.replace('- name:', '').trim()) }
                        currentCat.services.push(currentSvc)
                        continue
                    }
                    if (indent === 8 && currentSvc) {
                        const m = trimmed.match(/^([\w_]+)\s*:\s*(.*)$/)
                        if (m) currentSvc[m[1]] = this.yamlVal(m[2])
                    }
                }
            }

            if (categories.length > 0) result.categories = categories
            return result
        },

        yamlVal(str) {
            if (!str || str === '~' || str === 'null') return null
            if (str === 'true') return true
            if (str === 'false') return false
            if (/^\d+$/.test(str)) return parseInt(str)
            if ((str.startsWith('"') && str.endsWith('"')) ||
                (str.startsWith("'") && str.endsWith("'"))) {
                return str.slice(1, -1)
            }
            return str
        },
    },
}
</script>

<style lang="scss" scoped>
.settings-page {
    padding: 20px 32px;
    max-width: 800px;
    margin: 0 auto;

    &__header {
        margin-bottom: 32px;
        h2 { font-size: 24px; font-weight: 700; margin: 16px 0 0; }
    }

    &__section {
        margin-bottom: 32px;
        h3 {
            font-size: 18px; font-weight: 600; margin: 0 0 16px;
            padding-bottom: 8px; border-bottom: 1px solid var(--color-border);
        }
        > * + * { margin-top: 12px; }
    }

    &__field {
        display: flex; flex-direction: column; gap: 4px;
        label { font-size: 13px; font-weight: 500; color: var(--color-text-maxcontrast); }
    }

    &__hint {
        font-size: 13px; color: var(--color-text-maxcontrast); line-height: 1.5;
        code { background: var(--color-background-dark); padding: 1px 4px; border-radius: 3px; }
    }

    &__upload { margin-top: 8px; }

    &__icons {
        display: flex; flex-direction: column; gap: 4px; margin-top: 12px;
    }

    &__icon-item {
        display: flex; align-items: center; gap: 10px;
        padding: 6px 10px; background: var(--color-background-dark); border-radius: 8px;
        span { flex: 1; font-size: 13px; }
    }

    &__icon-preview { width: 28px; height: 28px; object-fit: contain; border-radius: 4px; }

    &__range-row {
        display: flex;
        align-items: center;
        gap: 8px;
        input[type="range"] { flex: 1; }
        span { min-width: 36px; text-align: right; }
    }

    &__ie-actions { display: flex; gap: 8px; flex-wrap: wrap; }

    &__ie-import {
        margin-top: 12px;
        display: flex; flex-direction: column; gap: 8px;
    }

    &__ie-mode { margin-top: 4px; }

    &__footer {
        padding: 20px 0;
        border-top: 1px solid var(--color-border);
    }
}

.color-swatch {
    display: inline-block;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    border: 1px solid var(--color-border);
}

.spacer-option {
    display: flex;
    align-items: center;
    gap: 10px;
    width: 100%;
    overflow: hidden;

    &__label {
        flex-shrink: 0;
        min-width: 90px;
    }

    &__preview {
        flex: 1;
        overflow: hidden;
        white-space: nowrap;
        font-size: 13px;
        line-height: 1;
    }
}
</style>
