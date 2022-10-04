import Vue from 'vue'
import Vuex from 'vuex'

import flashMessages from '@/store/flashMessages'

Vue.use(Vuex)

export default new Vuex.Store({
  modules: { flashMessages },
})
