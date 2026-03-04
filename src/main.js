/**
 * LinkBoard - Main entry point
 *
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import Vue from 'vue'
import { createPinia, PiniaVuePlugin } from 'pinia'
import App from './App.vue'
import router from './router.js'

Vue.use(PiniaVuePlugin)

const pinia = createPinia()

export default new Vue({
    el: '#linkboard-app',
    pinia,
    router,
    render: h => h(App),
})
