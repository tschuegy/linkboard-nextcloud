/**
 * LinkBoard - API Client
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

const BASE = '/apps/linkboard/api/v1'

function url(path) {
    return generateUrl(`${BASE}${path}`)
}

// ── Dashboard (full data) ─────────────────────────────
export const dashboardApi = {
    getAll: () => axios.get(url('/dashboard')),
}

// ── Categories ────────────────────────────────────────
export const categoryApi = {
    getAll: () => axios.get(url('/categories')),
    get: (id) => axios.get(url(`/categories/${id}`)),
    create: (data) => axios.post(url('/categories'), data),
    update: (id, data) => axios.put(url(`/categories/${id}`), data),
    delete: (id) => axios.delete(url(`/categories/${id}`)),
    reorder: (order) => axios.post(url('/categories/reorder'), { order: JSON.stringify(order) }),
    move: (id, parentId) => axios.put(url(`/categories/${id}/move`), { parentId }),
}

// ── Services ──────────────────────────────────────────
export const serviceApi = {
    getAll: () => axios.get(url('/services')),
    getByCategory: (categoryId) => axios.get(url(`/categories/${categoryId}/services`)),
    get: (id) => axios.get(url(`/services/${id}`)),
    create: (data) => axios.post(url('/services'), data),
    update: (id, data) => axios.put(url(`/services/${id}`), data),
    delete: (id) => axios.delete(url(`/services/${id}`)),
    reorder: (order) => axios.post(url('/services/reorder'), { order: JSON.stringify(order) }),
    move: (id, newCategoryId) => axios.put(url(`/services/${id}/move/${newCategoryId}`)),
}

// ── Settings ──────────────────────────────────────────
export const settingsApi = {
    getAll: () => axios.get(url('/settings')),
    updateAll: (settings) => axios.put(url('/settings'), { settings }),
    update: (key, value) => axios.put(url(`/settings/${key}`), { value }),
}

// ── Icons ─────────────────────────────────────────────
export const iconApi = {
    getAll: () => axios.get(url('/icons')),
    upload: (formData) => axios.post(url('/icons/upload'), formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
    }),
    delete: (filename) => axios.delete(url(`/icons/${filename}`)),
    getUrl: (filename) => generateUrl(`${BASE}/icons/${filename}`),
}

// ── Status (Phase 2) ─────────────────────────────────
export const statusApi = {
    getAll: () => axios.get(url('/status')),
    check: (serviceId) => axios.post(url(`/status/${serviceId}/check`)),
    checkAll: () => axios.post(url('/status/check-all')),
}

// ── Widgets ──────────────────────────────────────────
export const widgetApi = {
    getCatalog: () => axios.get(url('/widgets/catalog')),
    getAllData: () => axios.get(url('/widgets/data')),
    getData: (serviceId) => axios.get(url(`/widgets/${serviceId}/data`)),
}

// ── Import/Export (Phase 2) ──────────────────────────
// IMPORTANT: NC can't inject nested arrays as method params.
// We serialize the data as a JSON string in 'payload'.
export const importExportApi = {
    exportJsonUrl: () => generateUrl(`${BASE}/export/json`),
    exportYamlUrl: () => generateUrl(`${BASE}/export/yaml`),
    importJson: (data, mode = 'replace') => axios.post(url('/import/json'), {
        payload: JSON.stringify(data),
        mode,
    }),
    importYaml: (data, mode = 'replace') => axios.post(url('/import/yaml'), {
        payload: JSON.stringify(data),
        mode,
    }),
}
