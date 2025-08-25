import Vue from "vue"
import Vuex from "vuex"

import appStore from "@/store/appStore"
import flashMessages from "@/store/flashMessages"

Vue.use(Vuex)

export default new Vuex.Store({
  modules: { appStore, flashMessages },
})
