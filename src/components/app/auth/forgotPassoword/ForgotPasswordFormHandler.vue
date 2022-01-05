<template>
  <div class="forgot">
    <forgot-password-form :on-submit="submit" :is-sending="state.isSending" />
  </div>
</template>

<script>
import ForgotPasswordForm from './ForgotPasswordForm'
import { REQUESTS_STATE } from '../../../../store/modules/api/types'
import { API } from '../../../../api'
import { mapActions, mapGetters } from 'vuex'
import { AUTH } from '../../../../store/modules/auth/types'

export default {
  components: { ForgotPasswordForm },
  name: 'ForgotPasswordFormHandler',
  computed: {
    ...mapGetters(REQUESTS_STATE.NAMESPACE, [REQUESTS_STATE.GETTERS.GET_STATE]),
    state() {
      return this[REQUESTS_STATE.GETTERS.GET_STATE]([API.auth.forgotPassword.id])
    },
  },
  methods: {
    ...mapActions(AUTH.NAMESPACE, [AUTH.ACTIONS.FORGOT_PASSWORD_REQUEST]),
    submit(values) {
      return this[AUTH.ACTIONS.FORGOT_PASSWORD_REQUEST](values)
    },
  },
}
</script>

<style scoped>
.forgot {
  display: flex;
  justify-content: center;
}
</style>
