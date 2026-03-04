/**
 * LinkBoard - Router
 *
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import Vue from 'vue'
import VueRouter from 'vue-router'
import { generateUrl } from '@nextcloud/router'
import DashboardView from './components/Dashboard/DashboardView.vue'
import SettingsPage from './components/Settings/SettingsPage.vue'

Vue.use(VueRouter)

const routes = [
    {
        path: '/',
        name: 'dashboard',
        component: DashboardView,
    },
    {
        path: '/settings',
        name: 'settings',
        component: SettingsPage,
    },
]

const router = new VueRouter({
    base: generateUrl('/apps/linkboard'),
    routes,
})

export default router
