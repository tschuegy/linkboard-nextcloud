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

// Mount on the server's #content so NcContent replaces it instead of being
// nested inside it — nested, both containers apply the header offset and the
// bottom of the app ends up below the viewport (discussion #18)
export default new Vue({
    el: '#content',
    pinia,
    router,
    render: h => h(App),
})
