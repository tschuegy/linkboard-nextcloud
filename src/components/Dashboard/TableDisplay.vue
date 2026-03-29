<!--
LinkBoard - TableDisplay.vue
Inline-editable table widget for ServiceCard

SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
    <div class="table-display" :class="{ 'table-display--edit': editMode }" @click.stop>
        <div v-if="!columns.length && !editMode" class="table-display__empty">
            {{ t('linkboard', 'No table data') }}
        </div>
        <div v-else class="table-display__scroll">
            <table class="table-display__table">
                <thead>
                    <tr>
                        <th v-for="(col, ci) in columns" :key="'h-' + ci">
                            <input
                                v-if="editMode"
                                class="table-display__input table-display__input--header"
                                :value="col"
                                :placeholder="t('linkboard', 'Header')"
                                @input="onHeaderInput(ci, $event.target.value)" />
                            <span v-else>{{ col }}</span>
                            <button
                                v-if="editMode"
                                class="table-display__remove-col"
                                :title="t('linkboard', 'Remove column')"
                                @click="removeColumn(ci)">
                                &times;
                            </button>
                        </th>
                        <th v-if="editMode" class="table-display__add-col">
                            <button
                                :title="t('linkboard', 'Add column')"
                                @click="addColumn">
                                +
                            </button>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(row, ri) in rows" :key="'r-' + ri">
                        <td v-for="(cell, ci) in row" :key="'c-' + ri + '-' + ci">
                            <input
                                v-if="editMode"
                                class="table-display__input"
                                :value="cell"
                                @input="onCellInput(ri, ci, $event.target.value)" />
                            <span v-else>{{ cell }}</span>
                        </td>
                        <td v-if="editMode && columns.length" class="table-display__row-actions">
                            <button
                                :title="t('linkboard', 'Remove row')"
                                @click="removeRow(ri)">
                                &times;
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <button
            v-if="editMode && columns.length"
            class="table-display__add-row"
            @click="addRow">
            + {{ t('linkboard', 'Add row') }}
        </button>
    </div>
</template>

<script>
import { t } from '@nextcloud/l10n'
import { useDashboardStore } from '../../store/dashboard.js'

export default {
    name: 'TableDisplay',
    props: {
        service: { type: Object, required: true },
        editMode: { type: Boolean, default: false },
        manualColors: { type: Object, default: function() { return {} } },
    },
    data: function() {
        var cfg = this.service.widgetConfig || {}
        return {
            columns: cfg.columns ? cfg.columns.slice() : [],
            rows: cfg.rows ? cfg.rows.map(function(r) { return r.slice() }) : [],
            saveTimer: null,
        }
    },
    watch: {
        'service.widgetConfig': {
            handler: function(newCfg) {
                if (this.saveTimer) return
                var cfg = newCfg || {}
                this.columns = cfg.columns ? cfg.columns.slice() : []
                this.rows = cfg.rows ? cfg.rows.map(function(r) { return r.slice() }) : []
            },
            deep: true,
        },
    },
    beforeDestroy: function() {
        if (this.saveTimer) {
            clearTimeout(this.saveTimer)
            this.persistNow()
        }
    },
    methods: {
        t: t,
        onHeaderInput: function(ci, value) {
            this.columns.splice(ci, 1, value)
            this.debouncedSave()
        },
        onCellInput: function(ri, ci, value) {
            var row = this.rows[ri].slice()
            row[ci] = value
            this.rows.splice(ri, 1, row)
            this.debouncedSave()
        },
        addColumn: function() {
            this.columns.push(t('linkboard', 'New column'))
            for (var i = 0; i < this.rows.length; i++) {
                this.rows[i].push('')
            }
            if (this.rows.length === 0) {
                this.rows.push(this.columns.map(function() { return '' }))
            }
            this.persistNow()
        },
        removeColumn: function(ci) {
            this.columns.splice(ci, 1)
            for (var i = 0; i < this.rows.length; i++) {
                this.rows[i].splice(ci, 1)
            }
            this.persistNow()
        },
        addRow: function() {
            var row = this.columns.map(function() { return '' })
            this.rows.push(row)
            this.persistNow()
        },
        removeRow: function(ri) {
            this.rows.splice(ri, 1)
            this.persistNow()
        },
        debouncedSave: function() {
            var self = this
            if (this.saveTimer) clearTimeout(this.saveTimer)
            this.saveTimer = setTimeout(function() {
                self.saveTimer = null
                self.persistNow()
            }, 500)
        },
        persistNow: function() {
            var cfg = Object.assign({}, this.service.widgetConfig || {})
            cfg.columns = this.columns.slice()
            cfg.rows = this.rows.map(function(r) { return r.slice() })
            var store = useDashboardStore()
            store.updateService(this.service.id, {
                widgetType: 'table',
                widgetConfig: cfg,
            })
        },
    },
}
</script>

<style lang="scss" scoped>
.table-display {
    padding-top: 6px;
    border-top: 1px solid var(--color-border);
    margin-top: 8px;

    &__empty {
        font-size: 11px;
        color: var(--color-text-maxcontrast);
        padding: 4px 0;
    }

    &__scroll {
        overflow: auto;
        max-height: 300px;
    }

    &__table {
        width: 100%;
        border-collapse: collapse;
        font-size: 12px;

        th, td {
            padding: 3px 6px;
            border: 1px solid var(--color-border);
            white-space: nowrap;
            position: relative;
        }

        th {
            font-weight: 600;
            font-size: 11px;
            color: var(--color-text-maxcontrast);
            background: var(--color-background-dark);
        }
    }

    &__input {
        border: none;
        background: transparent;
        font-size: 12px;
        width: 100%;
        min-width: 60px;
        padding: 2px 0;
        color: var(--color-main-text);
        outline: none;

        &--header {
            font-weight: 600;
            font-size: 11px;
            color: var(--color-text-maxcontrast);
        }

        &:focus {
            background: var(--color-primary-element-light);
            border-radius: 2px;
        }
    }

    &__remove-col {
        position: absolute;
        top: -1px;
        right: -1px;
        width: 16px;
        height: 16px;
        font-size: 12px;
        line-height: 14px;
        text-align: center;
        background: var(--color-error);
        color: white;
        border: none;
        border-radius: 50%;
        cursor: pointer;
        opacity: 0;
        transition: opacity 0.15s;
        padding: 0;

        th:hover & { opacity: 1; }
    }

    &__add-col {
        border: none !important;
        background: transparent !important;
        padding: 0 4px !important;

        button {
            width: 24px;
            height: 24px;
            font-size: 16px;
            line-height: 22px;
            background: var(--color-background-dark);
            border: 1px dashed var(--color-border);
            border-radius: 4px;
            cursor: pointer;
            color: var(--color-text-maxcontrast);

            &:hover {
                background: var(--color-primary-element-light);
                border-color: var(--color-primary);
            }
        }
    }

    &__row-actions {
        border: none !important;
        padding: 0 2px !important;
        vertical-align: middle;

        button {
            width: 18px;
            height: 18px;
            font-size: 13px;
            line-height: 16px;
            text-align: center;
            background: var(--color-error);
            color: white;
            border: none;
            border-radius: 50%;
            cursor: pointer;
            opacity: 0;
            transition: opacity 0.15s;
            padding: 0;

            tr:hover & { opacity: 1; }
        }
    }

    &__add-row {
        display: block;
        width: 100%;
        margin-top: 4px;
        padding: 4px;
        font-size: 12px;
        background: var(--color-background-dark);
        border: 1px dashed var(--color-border);
        border-radius: 4px;
        cursor: pointer;
        color: var(--color-text-maxcontrast);

        &:hover {
            background: var(--color-primary-element-light);
            border-color: var(--color-primary);
        }
    }
}
</style>
