/**
 * LinkBoard - Dashboard Store (Pinia)
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { defineStore } from 'pinia'
import { t } from '@nextcloud/l10n'
import { dashboardApi, categoryApi, serviceApi, settingsApi, statusApi, widgetApi, importExportApi } from '../services/api.js'

export const useDashboardStore = defineStore('dashboard', {
    state: () => ({
        categories: [],
        settings: {},
        loading: false,
        editMode: false,
        searchQuery: '',
        editingServiceId: null,
        editingCategoryId: null,
        error: null,
        statusChecking: false,
        widgetData: {},
        widgetCatalog: [],
        widgetLoading: false,
    }),

    getters: {
        filteredCategories(state) {
            if (!state.searchQuery) return state.categories
            const q = state.searchQuery.toLowerCase()
            return state.categories
                .map(cat => ({
                    ...cat,
                    services: cat.services.filter(svc =>
                        svc.name.toLowerCase().includes(q)
                        || (svc.description && svc.description.toLowerCase().includes(q)),
                    ),
                }))
                .filter(cat => cat.services.length > 0 || cat.name.toLowerCase().includes(q))
        },

        totalServices(state) {
            return state.categories.reduce((sum, cat) => sum + (cat.services?.length || 0), 0)
        },

        editingService(state) {
            if (!state.editingServiceId) return null
            for (const cat of state.categories) {
                const svc = cat.services?.find(s => s.id === state.editingServiceId)
                if (svc) return svc
            }
            return null
        },

        editingCategory(state) {
            if (!state.editingCategoryId) return null
            return state.categories.find(c => c.id === state.editingCategoryId) || null
        },

        /** Count of services with ping enabled */
        pingEnabledCount(state) {
            let count = 0
            for (const cat of state.categories) {
                for (const svc of (cat.services || [])) {
                    if (svc.pingEnabled) count++
                }
            }
            return count
        },
    },

    actions: {
        // ── Load Data ───────────────────────────────────
        async fetchDashboard() {
            this.loading = true
            this.error = null
            try {
                const { data } = await dashboardApi.getAll()
                this.categories = data.categories || []
                this.settings = data.settings || {}
                // Fetch widget catalog and data in parallel
                this.fetchWidgetCatalog()
                this.fetchAllWidgetData()
            } catch (err) {
                this.error = t('linkboard', 'Failed to load dashboard')
                console.error('LinkBoard: Failed to load dashboard', err)
            } finally {
                this.loading = false
            }
        },

        // ── Category Actions ────────────────────────────
        async createCategory(categoryData) {
            try {
                const { data } = await categoryApi.create(categoryData)
                this.categories.push({ ...data, services: [] })
                return data
            } catch (err) {
                this.error = t('linkboard', 'Failed to create category')
                throw err
            }
        },

        async updateCategory(id, categoryData) {
            try {
                const { data } = await categoryApi.update(id, categoryData)
                const idx = this.categories.findIndex(c => c.id === id)
                if (idx !== -1) {
                    const services = this.categories[idx].services
                    this.categories[idx] = { ...data, services }
                }
                return data
            } catch (err) {
                this.error = t('linkboard', 'Failed to update category')
                throw err
            }
        },

        async deleteCategory(id) {
            try {
                await categoryApi.delete(id)
                this.categories = this.categories.filter(c => c.id !== id)
            } catch (err) {
                this.error = t('linkboard', 'Failed to delete category')
                throw err
            }
        },

        async reorderCategories(order) {
            try { await categoryApi.reorder(order) }
            catch (err) { this.error = t('linkboard', 'Failed to reorder categories'); throw err }
        },

        // ── Service Actions ─────────────────────────────
        async createService(serviceData) {
            try {
                const { data } = await serviceApi.create(serviceData)
                const cat = this.categories.find(c => c.id === data.categoryId)
                if (cat) {
                    if (!cat.services) cat.services = []
                    cat.services.push(data)
                }
                return data
            } catch (err) {
                this.error = err.response?.data?.error || t('linkboard', 'Failed to create service')
                throw err
            }
        },

        async updateService(id, serviceData) {
            try {
                const { data } = await serviceApi.update(id, serviceData)
                for (const cat of this.categories) {
                    const idx = cat.services?.findIndex(s => s.id === id)
                    if (idx !== undefined && idx !== -1) {
                        if (data.categoryId !== cat.id) {
                            cat.services.splice(idx, 1)
                            const newCat = this.categories.find(c => c.id === data.categoryId)
                            if (newCat) {
                                if (!newCat.services) newCat.services = []
                                newCat.services.push(data)
                            }
                        } else {
                            cat.services[idx] = data
                        }
                        break
                    }
                }
                return data
            } catch (err) {
                this.error = t('linkboard', 'Failed to update service')
                throw err
            }
        },

        async deleteService(id) {
            try {
                await serviceApi.delete(id)
                for (const cat of this.categories) {
                    const idx = cat.services?.findIndex(s => s.id === id)
                    if (idx !== undefined && idx !== -1) {
                        cat.services.splice(idx, 1)
                        break
                    }
                }
            } catch (err) { this.error = t('linkboard', 'Failed to delete service'); throw err }
        },

        async moveService(id, newCategoryId) {
            try {
                const { data } = await serviceApi.move(id, newCategoryId)
                for (const cat of this.categories) {
                    const idx = cat.services?.findIndex(s => s.id === id)
                    if (idx !== undefined && idx !== -1) { cat.services.splice(idx, 1); break }
                }
                const newCat = this.categories.find(c => c.id === newCategoryId)
                if (newCat) {
                    if (!newCat.services) newCat.services = []
                    newCat.services.push(data)
                }
                return data
            } catch (err) { this.error = t('linkboard', 'Failed to move service'); throw err }
        },

        async reorderServices(order) {
            try { await serviceApi.reorder(order) }
            catch (err) { this.error = t('linkboard', 'Failed to reorder services'); throw err }
        },

        // ── Settings Actions ────────────────────────────
        async updateSettings(settingsData) {
            try {
                const { data } = await settingsApi.updateAll(settingsData)
                this.settings = data
            } catch (err) { this.error = t('linkboard', 'Failed to update settings'); throw err }
        },

        // ── Status Actions (Phase 2) ────────────────────
        async checkServiceStatus(serviceId) {
            try {
                const { data } = await statusApi.check(serviceId)
                // Update status in local state
                for (const cat of this.categories) {
                    const svc = cat.services?.find(s => s.id === serviceId)
                    if (svc) { svc.status = data; break }
                }
                return data
            } catch (err) { console.error('Status check failed', err) }
        },

        async checkAllStatuses() {
            this.statusChecking = true
            try {
                const { data } = await statusApi.checkAll()
                // Update all statuses in local state
                if (data.statuses) {
                    for (const cat of this.categories) {
                        for (const svc of (cat.services || [])) {
                            if (data.statuses[svc.id]) {
                                svc.status = data.statuses[svc.id]
                            }
                        }
                    }
                }
                return data
            } catch (err) {
                this.error = t('linkboard', 'Status check failed')
                throw err
            } finally {
                this.statusChecking = false
            }
        },

        // ── Widget Actions ───────────────────────────────
        async fetchWidgetCatalog() {
            try {
                const { data } = await widgetApi.getCatalog()
                this.widgetCatalog = data || []
            } catch (err) {
                console.error('LinkBoard: Failed to load widget catalog', err)
            }
        },

        async fetchAllWidgetData() {
            this.widgetLoading = true
            try {
                const { data } = await widgetApi.getAllData()
                this.widgetData = data || {}
            } catch (err) {
                console.error('LinkBoard: Failed to load widget data', err)
            } finally {
                this.widgetLoading = false
            }
        },

        async fetchWidgetData(serviceId) {
            try {
                const { data } = await widgetApi.getData(serviceId)
                this.widgetData[serviceId] = data
            } catch (err) {
                console.error('LinkBoard: Failed to load widget data for service ' + serviceId, err)
            }
        },

        // ── Import/Export (Phase 2) ─────────────────────
        async importData(data, mode = 'replace') {
            try {
                const result = await importExportApi.importJson(data, mode)
                await this.fetchDashboard() // Reload after import
                return result.data
            } catch (err) {
                this.error = err.response?.data?.error || t('linkboard', 'Import failed')
                throw err
            }
        },

        // ── UI State ────────────────────────────────────
        toggleEditMode() {
            this.editMode = !this.editMode
            if (!this.editMode) {
                this.editingServiceId = null
                this.editingCategoryId = null
            }
        },

        selectServiceForEdit(serviceId) {
            this.editingServiceId = serviceId
            this.editingCategoryId = null
        },

        selectCategoryForEdit(categoryId) {
            this.editingCategoryId = categoryId
            this.editingServiceId = null
        },

        clearSelection() {
            this.editingServiceId = null
            this.editingCategoryId = null
        },

        clearError() { this.error = null },
    },
})
