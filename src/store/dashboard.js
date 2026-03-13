/**
 * LinkBoard - Dashboard Store (Pinia)
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { defineStore } from 'pinia'
import { t } from '@nextcloud/l10n'
import { dashboardApi, categoryApi, serviceApi, settingsApi, statusApi, widgetApi, resourceApi, importExportApi } from '../services/api.js'

function findCategoryInTree(categories, id) {
    for (var i = 0; i < categories.length; i++) {
        if (categories[i].id === id) {
            return { category: categories[i], parent: null, index: i, list: categories }
        }
        var children = categories[i].children || []
        for (var j = 0; j < children.length; j++) {
            if (children[j].id === id) {
                return { category: children[j], parent: categories[i], index: j, list: children }
            }
        }
    }
    return null
}

function forEachCategory(categories, fn) {
    for (var i = 0; i < categories.length; i++) {
        fn(categories[i])
        var children = categories[i].children || []
        for (var j = 0; j < children.length; j++) {
            fn(children[j])
        }
    }
}

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
        resourceData: {},
        statusHistory: {},
        statusHistoryLoading: false,
        appVersion: null,
        latestVersion: null,
        latestVersionUrl: null,
    }),

    getters: {
        filteredCategories(state) {
            if (!state.searchQuery) return state.categories
            var q = state.searchQuery.toLowerCase()
            function matchSvc(svc) {
                return svc.name.toLowerCase().includes(q)
                    || (svc.description && svc.description.toLowerCase().includes(q))
            }
            return state.categories
                .map(function(cat) {
                    var filteredChildren = (cat.children || [])
                        .map(function(child) {
                            return { ...child, services: child.services.filter(matchSvc) }
                        })
                        .filter(function(child) {
                            return child.services.length > 0 || child.name.toLowerCase().includes(q)
                        })
                    return {
                        ...cat,
                        services: cat.services.filter(matchSvc),
                        children: filteredChildren,
                    }
                })
                .filter(function(cat) {
                    return cat.services.length > 0 || cat.children.length > 0 || cat.name.toLowerCase().includes(q)
                })
        },

        totalServices(state) {
            var sum = 0
            forEachCategory(state.categories, function(cat) { sum += (cat.services?.length || 0) })
            return sum
        },

        editingService(state) {
            if (!state.editingServiceId) return null
            var result = null
            forEachCategory(state.categories, function(cat) {
                if (result) return
                var svc = cat.services?.find(function(s) { return s.id === state.editingServiceId })
                if (svc) result = svc
            })
            return result
        },

        editingCategory(state) {
            if (!state.editingCategoryId) return null
            var found = findCategoryInTree(state.categories, state.editingCategoryId)
            return found ? found.category : null
        },

        /** Count of services with ping enabled */
        pingEnabledCount(state) {
            var count = 0
            forEachCategory(state.categories, function(cat) {
                for (var s of (cat.services || [])) {
                    if (s.pingEnabled) count++
                }
            })
            return count
        },

        /** All top-level categories (for parent selector) */
        topLevelCategories(state) {
            return state.categories.filter(function(c) { return !c.parentId })
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
                this.appVersion = data.version || null
                this.latestVersion = data.latestVersion || null
                this.latestVersionUrl = data.latestVersionUrl || null
                // Fetch widget catalog, widget data, and resource data in parallel
                this.fetchWidgetCatalog()
                this.fetchAllWidgetData()
                this.fetchAllResourceData()
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
                var newCat = { ...data, services: [], children: [] }
                if (data.parentId) {
                    var parentFound = findCategoryInTree(this.categories, data.parentId)
                    if (parentFound) {
                        if (!parentFound.category.children) parentFound.category.children = []
                        parentFound.category.children.push(newCat)
                    } else {
                        this.categories.push(newCat)
                    }
                } else {
                    this.categories.push(newCat)
                }
                return data
            } catch (err) {
                this.error = t('linkboard', 'Failed to create category')
                throw err
            }
        },

        async updateCategory(id, categoryData) {
            try {
                const { data } = await categoryApi.update(id, categoryData)
                var found = findCategoryInTree(this.categories, id)
                if (found) {
                    var services = found.category.services
                    var children = found.category.children
                    Object.assign(found.category, data, { services, children })
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
                var found = findCategoryInTree(this.categories, id)
                if (found) {
                    // Promote children to top-level
                    var children = found.category.children || []
                    for (var i = 0; i < children.length; i++) {
                        children[i].parentId = null
                        this.categories.push(children[i])
                    }
                    found.list.splice(found.index, 1)
                }
            } catch (err) {
                this.error = t('linkboard', 'Failed to delete category')
                throw err
            }
        },

        async moveCategoryToParent(categoryId, parentId) {
            try {
                const { data } = await categoryApi.move(categoryId, parentId)
                var found = findCategoryInTree(this.categories, categoryId)
                if (found) {
                    var cat = found.category
                    found.list.splice(found.index, 1)
                    cat.parentId = parentId
                    if (parentId) {
                        var parentFound = findCategoryInTree(this.categories, parentId)
                        if (parentFound) {
                            if (!parentFound.category.children) parentFound.category.children = []
                            parentFound.category.children.push(cat)
                        }
                    } else {
                        this.categories.push(cat)
                    }
                }
                return data
            } catch (err) {
                this.error = t('linkboard', 'Failed to move category')
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
                var catFound = findCategoryInTree(this.categories, data.categoryId)
                if (catFound) {
                    if (!catFound.category.services) catFound.category.services = []
                    catFound.category.services.push(data)
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
                var removed = false
                forEachCategory(this.categories, function(cat) {
                    if (removed) return
                    var idx = cat.services?.findIndex(function(s) { return s.id === id })
                    if (idx !== undefined && idx !== -1) {
                        if (data.categoryId !== cat.id) {
                            cat.services.splice(idx, 1)
                            removed = true
                        } else {
                            cat.services[idx] = data
                            removed = true
                        }
                    }
                })
                if (removed && data.categoryId) {
                    var targetCat = findCategoryInTree(this.categories, data.categoryId)
                    if (targetCat && !targetCat.category.services?.find(function(s) { return s.id === id })) {
                        if (!targetCat.category.services) targetCat.category.services = []
                        targetCat.category.services.push(data)
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
                forEachCategory(this.categories, function(cat) {
                    var idx = cat.services?.findIndex(function(s) { return s.id === id })
                    if (idx !== undefined && idx !== -1) {
                        cat.services.splice(idx, 1)
                    }
                })
            } catch (err) { this.error = t('linkboard', 'Failed to delete service'); throw err }
        },

        async moveService(id, newCategoryId) {
            try {
                const { data } = await serviceApi.move(id, newCategoryId)
                forEachCategory(this.categories, function(cat) {
                    var idx = cat.services?.findIndex(function(s) { return s.id === id })
                    if (idx !== undefined && idx !== -1) { cat.services.splice(idx, 1) }
                })
                var newCatFound = findCategoryInTree(this.categories, newCategoryId)
                if (newCatFound) {
                    if (!newCatFound.category.services) newCatFound.category.services = []
                    newCatFound.category.services.push(data)
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
                forEachCategory(this.categories, function(cat) {
                    var svc = cat.services?.find(function(s) { return s.id === serviceId })
                    if (svc) { svc.status = data }
                })
                return data
            } catch (err) { console.error('Status check failed', err) }
        },

        async checkAllStatuses() {
            this.statusChecking = true
            try {
                const { data } = await statusApi.checkAll()
                if (data.statuses) {
                    forEachCategory(this.categories, function(cat) {
                        for (var svc of (cat.services || [])) {
                            if (data.statuses[svc.id]) {
                                svc.status = data.statuses[svc.id]
                            }
                        }
                    })
                }
                return data
            } catch (err) {
                this.error = t('linkboard', 'Status check failed')
                throw err
            } finally {
                this.statusChecking = false
            }
        },

        // ── Status History Actions ───────────────────────
        async fetchStatusHistory(serviceId, period) {
            this.statusHistoryLoading = true
            try {
                const { data } = await statusApi.getHistory(serviceId, period)
                this.statusHistory = { ...this.statusHistory, [serviceId]: data }
                return data
            } catch (err) {
                console.error('LinkBoard: Failed to load status history', err)
                throw err
            } finally {
                this.statusHistoryLoading = false
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
                this.widgetData = { ...this.widgetData, [serviceId]: data }
            } catch (err) {
                console.error('LinkBoard: Failed to load widget data for service ' + serviceId, err)
            }
        },

        // ── Resource Actions ─────────────────────────────
        async fetchAllResourceData() {
            var self = this
            forEachCategory(this.categories, function(cat) {
                if (cat.type === 'resources') {
                    self.fetchResourceData(cat.id)
                }
            })
        },

        async fetchResourceData(categoryId) {
            try {
                const { data } = await resourceApi.getData(categoryId)
                this.resourceData = { ...this.resourceData, [categoryId]: data }
            } catch (err) {
                console.error('LinkBoard: Failed to load resource data for category ' + categoryId, err)
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
