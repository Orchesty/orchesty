<template>
  <auth-layout>
    <forgot-password-form :on-submit="submit" :is-sending="state.isSending" />
  </auth-layout>
</template>

<script>
import ForgotPasswordForm from "@/components/app/auth/forms/ForgotPasswordForm"
import { REQUESTS_STATE } from "@/store/modules/api/types"
import { API } from "@/api"
import { mapActions, mapGetters } from "vuex"
import { AUTH } from "@/store/modules/auth/types"
import { ROUTES } from "@/services/enums/routerEnums"
import AuthLayout from "@/components/layout/auth/AuthLayout"

export default {
  components: { AuthLayout, ForgotPasswordForm },
  name: "ForgotPasswordPage",
  computed: {
    ...mapGetters(REQUESTS_STATE.NAMESPACE, [REQUESTS_STATE.GETTERS.GET_STATE]),
    state() {
      return this[REQUESTS_STATE.GETTERS.GET_STATE]([
        API.auth.forgotPassword.id,
      ])
    },
  },
  methods: {
    ...mapActions(AUTH.NAMESPACE, [AUTH.ACTIONS.FORGOT_PASSWORD_REQUEST]),
    async submit(values) {
      const response = await this[AUTH.ACTIONS.FORGOT_PASSWORD_REQUEST](values)
      if (response) {
        await this.$router.push({
          name: ROUTES.FORGOT_PASSWORD_SENT,
          params: { email: response },
        })
      }
    },
  },
}
</script>

<style scoped></style>
