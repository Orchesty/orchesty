<template>
  <auth-layout>
    <v-alert v-if="email === null" type="error">
      {{ $t('forgotPassword.notValidToken') }}
    </v-alert>
    <password-form v-else :email="email" :on-submit="submit" :is-sending="setPasswordState.isSending" />
  </auth-layout>
</template>

<script>
import { REQUESTS_STATE } from '@/store/modules/api/types'
import { API } from '@/api'
import { mapActions, mapGetters } from 'vuex'
import { AUTH } from '@/store/modules/auth/types'
import AuthLayout from '@/components/layout/auth/AuthLayout'
import PasswordForm from '@/components/app/auth/forms/PasswordForm'

export default {
  components: { PasswordForm, AuthLayout },
  name: 'ResetPasswordPage',
  async created() {
    this.email = await this[AUTH.ACTIONS.CHECK_TOKEN_REQUEST]({ token: this.$route.params.token })
  },
  computed: {
    ...mapGetters(REQUESTS_STATE.NAMESPACE, [REQUESTS_STATE.GETTERS.GET_STATE]),
    state() {
      return this[REQUESTS_STATE.GETTERS.GET_STATE]([API.auth.checkToken.id])
    },
    setPasswordState() {
      return this[REQUESTS_STATE.GETTERS.GET_STATE]([API.auth.setPassword.id])
    },
  },
  data() {
    return {
      email: null,
    }
  },
  methods: {
    ...mapActions(AUTH.NAMESPACE, [AUTH.ACTIONS.CHECK_TOKEN_REQUEST, AUTH.ACTIONS.SET_PASSWORD_REQUEST]),
    submit(values) {
      this[AUTH.ACTIONS.SET_PASSWORD_REQUEST]({
        token: this.$route.params.token,
        password: values.password,
      })
    },
  },
}
</script>
