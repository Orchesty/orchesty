import Vue from 'vue'
import Vuex from 'vuex'
import { flashMessages } from './modules/flashMessages/flashMessages'

Vue.use(Vuex)

export default new Vuex.Store({
  modules: {
    flashMessages,
  },
})
