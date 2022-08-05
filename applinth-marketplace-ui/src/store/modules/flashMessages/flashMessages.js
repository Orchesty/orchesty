export const flashMessages = {
  namespaced: true,
  state: {
    flashMessage: null,
  },
  actions: {
    flashMessageSet(context, payload) {
      context.commit('flashMessageSet', payload)
    },
    flashMessageRemove(context) {
      context.commit('flashMessageRemove')
    },
  },
  mutations: {
    flashMessageSet(state, params) {
      if (state.flashMessage) {
        state.flashMessage = null
      }

      state.flashMessage = params
    },
    flashMessageRemove(state) {
      state.flashMessage = null
    },
  },
}
