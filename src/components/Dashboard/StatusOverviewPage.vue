<!--
LinkBoard - StatusOverviewPage.vue
Status overview page showing charts for all ping-enabled services

SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
    <div class="status-overview">
        <div class="status-overview__header">
            <NcButton type="tertiary" :aria-label="t('linkboard', 'Back to LinkBoard')" @click="$router.push('/')">
                <template #icon>
                    <ArrowLeftIcon :size="20" />
                </template>
                {{ t('linkboard', 'Back to LinkBoard') }}
            </NcButton>
            <h2>{{ t('linkboard', 'Status overview') }}</h2>
        </div>

        <!-- Period Toggle -->
        <div class="status-overview__period">
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

        <NcLoadingIcon v-if="loading" :size="64" class="status-overview__loading" />

        <div v-else-if="pingServices.length === 0" class="status-overview__empty">
            {{ t('linkboard', 'No history available') }}
        </div>

        <div v-else class="status-overview__grid">
            <div v-for="svc in pingServices" :key="svc.id" class="status-overview__card">
                <div class="status-overview__card-header">
                    <span class="status-overview__card-name">{{ svc.name }}</span>
                    <template v-if="allHistoryData[svc.id]">
                        <span class="status-overview__badge" :class="'status-overview__badge--' + allHistoryData[svc.id].currentStatus">
                            {{ statusLabel(allHistoryData[svc.id].currentStatus) }}
                        </span>
                    </template>
                </div>
                <div v-if="allHistoryData[svc.id]" class="status-overview__card-stats">
                    <span :class="uptimeClass(allHistoryData[svc.id].uptimePercent)">
                        {{ allHistoryData[svc.id].uptimePercent !== null ? allHistoryData[svc.id].uptimePercent + '%' : '—' }}
                    </span>
                    <span class="status-overview__failures">
                        {{ allHistoryData[svc.id].totalFailures }} {{ t('linkboard', 'Total failures').toLowerCase() }}
                    </span>
                </div>
                <ResponseTimeChart
                    v-if="allHistoryData[svc.id]"
                    :history-data="allHistoryData[svc.id]" />
            </div>
        </div>
    </div>
</template>

<script>
import { t } from '@nextcloud/l10n'
import { NcButton, NcLoadingIcon } from '@nextcloud/vue'
import { mapState, mapActions } from 'pinia'
import { useDashboardStore } from '../../store/dashboard.js'
import { statusApi } from '../../services/api.js'
import ResponseTimeChart from './ResponseTimeChart.vue'
import ArrowLeftIcon from 'vue-material-design-icons/ArrowLeft.vue'

export default {
    name: 'StatusOverviewPage',
    components: { NcButton, NcLoadingIcon, ResponseTimeChart, ArrowLeftIcon },
    data: function() {
        return {
            period: '24h',
            loading: false,
            allHistoryData: {},
        }
    },
    computed: {
        ...mapState(useDashboardStore, ['categories']),
        pingServices: function() {
            var services = []
            for (var i = 0; i < this.categories.length; i++) {
                var cat = this.categories[i]
                for (var j = 0; j < (cat.services || []).length; j++) {
                    if (cat.services[j].pingEnabled) {
                        services.push(cat.services[j])
                    }
                }
                for (var k = 0; k < (cat.children || []).length; k++) {
                    var child = cat.children[k]
                    for (var l = 0; l < (child.services || []).length; l++) {
                        if (child.services[l].pingEnabled) {
                            services.push(child.services[l])
                        }
                    }
                }
            }
            return services
        },
    },
    mounted: function() {
        var self = this
        if (this.categories.length === 0) {
            this.fetchDashboard().then(function() {
                self.loadAllHistory()
            })
        } else {
            this.loadAllHistory()
        }
    },
    methods: {
        t,
        ...mapActions(useDashboardStore, ['fetchDashboard']),
        switchPeriod: function(p) {
            this.period = p
            this.loadAllHistory()
        },
        loadAllHistory: function() {
            var self = this
            self.loading = true
            statusApi.getAllHistory(self.period).then(function(res) {
                self.allHistoryData = res.data
            }).catch(function() {
                self.allHistoryData = {}
            }).finally(function() {
                self.loading = false
            })
        },
        statusLabel: function(s) {
            if (s === 'online') return t('linkboard', 'Online')
            if (s === 'offline') return t('linkboard', 'Offline')
            return t('linkboard', 'Unknown')
        },
        uptimeClass: function(pct) {
            if (pct === null) return ''
            if (pct >= 99) return 'status-overview__uptime--good'
            if (pct >= 95) return 'status-overview__uptime--warn'
            return 'status-overview__uptime--bad'
        },
    },
}
</script>

<style lang="scss" scoped>
.status-overview {
    padding: 20px 32px;
    max-width: 1800px;
    margin: 0 auto;

    &__header {
        display: flex;
        align-items: center;
        gap: 16px;
        margin-bottom: 24px;

        h2 {
            font-size: 24px;
            font-weight: 700;
            margin: 0;
        }
    }

    &__period {
        display: flex;
        gap: 4px;
        margin-bottom: 24px;

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

            outline: none;

            &:focus-visible {
                outline: 2px solid var(--color-primary);
                outline-offset: 1px;
            }

            &:hover:not(.active) {
                background: var(--color-background-hover);
            }
        }
    }

    &__loading {
        display: flex;
        justify-content: center;
        margin-top: 80px;
    }

    &__empty {
        text-align: center;
        padding: 64px 0;
        color: var(--color-text-maxcontrast);
        font-size: 14px;
    }

    &__grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(480px, 1fr));
        gap: 20px;
    }

    &__card {
        background: var(--color-background-dark);
        border-radius: 12px;
        padding: 16px;
    }

    &__card-header {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 8px;
    }

    &__card-name {
        font-size: 15px;
        font-weight: 600;
    }

    &__badge {
        font-size: 11px;
        font-weight: 600;
        padding: 2px 8px;
        border-radius: 10px;
        text-transform: uppercase;

        &--online {
            background: rgba(34, 197, 94, 0.15);
            color: #22c55e;
        }
        &--offline {
            background: rgba(239, 68, 68, 0.15);
            color: #ef4444;
        }
        &--unknown {
            background: rgba(163, 163, 163, 0.15);
            color: #a3a3a3;
        }
    }

    &__card-stats {
        display: flex;
        gap: 16px;
        margin-bottom: 12px;
        font-size: 13px;
        color: var(--color-text-maxcontrast);
    }

    &__uptime--good { color: #22c55e; font-weight: 700; }
    &__uptime--warn { color: #f59e0b; font-weight: 700; }
    &__uptime--bad { color: #ef4444; font-weight: 700; }

    &__failures {
        color: var(--color-text-maxcontrast);
    }
}

@media (max-width: 540px) {
    .status-overview__grid {
        grid-template-columns: 1fr;
    }
}
</style>
