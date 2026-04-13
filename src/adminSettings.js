/**
 * LinkBoard - Admin Settings entry point
 *
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import Vue from 'vue'
import AdminSettings from './components/AdminSettings/AdminSettings.vue'

export default new Vue({
	el: '#linkboard-admin-settings',
	render: h => h(AdminSettings),
})
