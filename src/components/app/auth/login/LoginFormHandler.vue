<template>
  <login-form :on-submit="submit" :is-sending="state.isSending" />
</template>

<script>
import LoginForm from './LoginForm'
import { REQUESTS_STATE } from '../../../../store/modules/api/types'
import { API } from '../../../../api'
import { mapActions, mapGetters } from 'vuex'
import { AUTH } from '../../../../store/modules/auth/types'

export default {
  components: { LoginForm },
  name: 'LoginFormHandler',
  computed: {
    ...mapGetters(REQUESTS_STATE.NAMESPACE, [REQUESTS_STATE.GETTERS.GET_STATE]),
    state() {
      return this[REQUESTS_STATE.GETTERS.GET_STATE]([API.auth.login.id])
    },
  },
  methods: {
    ...mapActions(AUTH.NAMESPACE, [AUTH.ACTIONS.LOGIN_REQUEST]),
    submit(values) {
      this[AUTH.ACTIONS.LOGIN_REQUEST](values)
    },
  },
}
</script>
