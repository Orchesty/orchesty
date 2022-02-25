<template>
  <auth-layout>
    <auth-split-layout>
      <template #heading> Done, instructions sent! </template>
      <template #form>
        <p class="body-2">
          Check your email <b>{{ $route.params.email }}</b> and follow the instructions.
        </p>
      </template>
    </auth-split-layout>
  </auth-layout>
</template>

<script>
import { REQUESTS_STATE } from '@/store/modules/api/types'
import { API } from '@/api'
import { mapActions, mapGetters } from 'vuex'
import { AUTH } from '@/store/modules/auth/types'
import AuthLayout from '@/components/layout/auth/AuthLayout'
import AuthSplitLayout from '@/components/app/auth/layout/AuthSplitLayout'

export default {
  components: { AuthSplitLayout, AuthLayout },
  name: 'ForgotPasswordPage',
  computed: {
    ...mapGetters(REQUESTS_STATE.NAMESPACE, [REQUESTS_STATE.GETTERS.GET_STATE]),
    state() {
      return this[REQUESTS_STATE.GETTERS.GET_STATE]([API.auth.forgotPassword.id])
    },
  },
  methods: {
    ...mapActions(AUTH.NAMESPACE, [AUTH.ACTIONS.FORGOT_PASSWORD_REQUEST]),
    async submit(values) {
      return await this[AUTH.ACTIONS.FORGOT_PASSWORD_REQUEST](values)
    },
  },
}
</script>

<style scoped></style>
