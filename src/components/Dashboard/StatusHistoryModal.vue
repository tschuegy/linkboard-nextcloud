<!--
LinkBoard - StatusHistoryModal.vue
Modal showing status check history with response time chart

SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
    <NcDialog
        :open="open"
        :name="t('linkboard', 'Status History')"
        size="large"
        @update:open="$emit('update:open', $event)">
        <div class="status-history">
            <!-- Period Toggle -->
            <div class="status-history__period">
                <button
                    :class="{ active: period === '1h' }"
                    @click="switchPeriod('1h')">
                    {{ t('linkboard', 'Last hour') }}
                </button>
                <button
                    :class="{ active: period === '3h' }"
                    @click="switchPeriod('3h')">
                    {{ t('linkboard', 'Last 3 hours') }}
                </button>
                <button
                    :class="{ active: period === '24h' }"
                    @click="switchPeriod('24h')">
                    {{ t('linkboard', 'Last 24 hours') }}
                </button>
                <button
                    :class="{ active: period === '7d' }"
                    @click="switchPeriod('7d')">
                    {{ t('linkboard', 'Last 7 days') }}
                </button>
            </div>

            <NcLoadingIcon v-if="loading" :size="48" class="status-history__loading" />

            <template v-else-if="historyData">
                <!-- Summary -->
                <div class="status-history__summary">
                    <div class="status-history__stat">
                        <span class="status-history__stat-label">{{ t('linkboard', 'Uptime') }}</span>
                        <span class="status-history__stat-value" :class="uptimeClass">
                            {{ historyData.uptimePercent !== null ? historyData.uptimePercent + '%' : '—' }}
                        </span>
                    </div>
                    <div class="status-history__stat">
                        <span class="status-history__stat-label">{{ t('linkboard', 'Total failures') }}</span>
                        <span class="status-history__stat-value status-history__stat-value--failures">
                            {{ historyData.totalFailures }}
                        </span>
                    </div>
                    <div class="status-history__stat">
                        <span class="status-history__stat-label">Status</span>
                        <span class="status-history__stat-value" :class="'status-history__stat-value--' + historyData.currentStatus">
                            {{ statusLabel }}
                        </span>
                    </div>
                </div>

                <!-- Response Time Chart -->
                <ResponseTimeChart :history-data="historyData" />

                <!-- No history -->
                <div v-if="!historyData.history || historyData.history.length === 0" class="status-history__empty">
                    {{ t('linkboard', 'No history available') }}
                </div>
            </template>

            <div v-else-if="errorMsg" class="status-history__error">
                {{ t('linkboard', 'Failed to load status history') }}
            </div>
        </div>
    </NcDialog>
</template>

<script>
import { t } from '@nextcloud/l10n'
import { NcDialog, NcLoadingIcon } from '@nextcloud/vue'
import { mapState, mapActions } from 'pinia'
import { useDashboardStore } from '../../store/dashboard.js'
import ResponseTimeChart from './ResponseTimeChart.vue'

export default {
    name: 'StatusHistoryModal',
    components: { NcDialog, NcLoadingIcon, ResponseTimeChart },
    props: {
        open: { type: Boolean, default: false },
        serviceId: { type: Number, default: null },
        serviceName: { type: String, default: '' },
    },
    data: function() {
        return {
            period: '24h',
            errorMsg: null,
        }
    },
    computed: {
        ...mapState(useDashboardStore, ['statusHistory', 'statusHistoryLoading']),
        loading: function() {
            return this.statusHistoryLoading
        },
        historyData: function() {
            if (!this.serviceId) return null
            return this.statusHistory[this.serviceId] || null
        },
        statusLabel: function() {
            if (!this.historyData) return ''
            var s = this.historyData.currentStatus
            if (s === 'online') return t('linkboard', 'Online')
            if (s === 'offline') return t('linkboard', 'Offline')
            return t('linkboard', 'Unknown')
        },
        uptimeClass: function() {
            if (!this.historyData || this.historyData.uptimePercent === null) return ''
            if (this.historyData.uptimePercent >= 99) return 'status-history__stat-value--online'
            if (this.historyData.uptimePercent >= 95) return 'status-history__stat-value--warn'
            return 'status-history__stat-value--offline'
        },
    },
    watch: {
        open: function(val) {
            if (val && this.serviceId) {
                this.loadHistory()
            }
        },
        serviceId: function() {
            if (this.open && this.serviceId) {
                this.loadHistory()
            }
        },
    },
    methods: {
        t,
        switchPeriod: function(p) {
            this.period = p
            this.loadHistory()
        },
        loadHistory: function() {
            var self = this
            self.errorMsg = null
            self.fetchStatusHistory(self.serviceId, self.period).catch(function() {
                self.errorMsg = true
            })
        },
        ...mapActions(useDashboardStore, ['fetchStatusHistory']),
    },
}
</script>

<style lang="scss" scoped>
.status-history {
    padding: 8px 0;

    &__period {
        display: flex;
        gap: 4px;
        margin-bottom: 16px;

        button {
            padding: 6px 16px;
            border: 1px solid var(--color-border);
            border-radius: 20px;
            background: transparent;
            color: var(--color-main-text);
            cursor: pointer;
            font-size: 13px;
            transition: all 0.15s;

            &.active {
                background: var(--color-primary);
                color: var(--color-primary-text);
                border-color: var(--color-primary);
            }

            &:hover:not(.active) {
                background: var(--color-background-hover);
            }
        }
    }

    &__loading {
        display: flex;
        justify-content: center;
        padding: 32px 0;
    }

    &__summary {
        display: flex;
        gap: 24px;
        margin-bottom: 20px;
        padding: 12px 16px;
        background: var(--color-background-dark);
        border-radius: 10px;
    }

    &__stat {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    &__stat-label {
        font-size: 12px;
        color: var(--color-text-maxcontrast);
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    &__stat-value {
        font-size: 20px;
        font-weight: 700;

        &--online { color: #22c55e; }
        &--offline { color: #ef4444; }
        &--warn { color: #f59e0b; }
        &--unknown { color: #a3a3a3; }
        &--failures { color: var(--color-main-text); }
    }

    &__empty, &__error {
        text-align: center;
        padding: 32px 0;
        color: var(--color-text-maxcontrast);
        font-size: 14px;
    }
}
</style>
