<template>
  <auth-layout>
    <login-form :on-submit="submit" :is-sending="state.isSending" />
  </auth-layout>
</template>

<script>
import LoginForm from '@/components/app/auth/forms/LoginForm'
import { REQUESTS_STATE } from '@/store/modules/api/types'
import { API } from '@/api'
import { mapActions, mapGetters } from 'vuex'
import { AUTH } from '@/store/modules/auth/types'
import AuthLayout from '@/components/layout/auth/AuthLayout'

export default {
  components: { AuthLayout, LoginForm },
  name: 'LoginPage',
  computed: {
    ...mapGetters(REQUESTS_STATE.NAMESPACE, [REQUESTS_STATE.GETTERS.GET_STATE]),
    state() {
      return this[REQUESTS_STATE.GETTERS.GET_STATE]([API.auth.login.id])
    },
  },
  methods: {
    ...mapActions(AUTH.NAMESPACE, [AUTH.ACTIONS.LOGIN_REQUEST]),
    async submit(values) {
      await this[AUTH.ACTIONS.LOGIN_REQUEST](values)
    },
  },
}
</script>
