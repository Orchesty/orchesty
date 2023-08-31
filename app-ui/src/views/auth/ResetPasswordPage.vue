<template>
  <auth-layout>
    <v-alert v-if="!email" type="error">
      {{ $t("page.text.notValidToken") }}
    </v-alert>
    <auth-split-layout v-else>
      <template #heading> {{ $t("page.heading.setNewPassword") }} </template>
      <template #form>
        <password-form
          :email="email"
          :on-submit="submit"
          :is-sending="state.isSending"
        />
      </template>
    </auth-split-layout>
  </auth-layout>
</template>

<script>
import { REQUESTS_STATE } from "@/store/modules/api/types"
import { API } from "@/api"
import { mapActions, mapGetters } from "vuex"
import { AUTH } from "@/store/modules/auth/types"
import AuthLayout from "@/components/layout/auth/AuthLayout"
import PasswordForm from "@/components/app/auth/forms/PasswordForm"
import AuthSplitLayout from "@/components/app/auth/layout/AuthSplitLayout"
import { ROUTES } from "@/services/enums/routerEnums"

export default {
  components: { AuthSplitLayout, PasswordForm, AuthLayout },
  name: "ResetPasswordPage",
  async created() {
    this.email = await this[AUTH.ACTIONS.CHECK_TOKEN_REQUEST]({
      token: this.$route.params.token,
    })
  },
  computed: {
    ...mapGetters(REQUESTS_STATE.NAMESPACE, [REQUESTS_STATE.GETTERS.GET_STATE]),
    state() {
      return this[REQUESTS_STATE.GETTERS.GET_STATE]([API.auth.setPassword.id])
    },
  },
  data() {
    return {
      email: null,
    }
  },
  methods: {
    ...mapActions(AUTH.NAMESPACE, [
      AUTH.ACTIONS.CHECK_TOKEN_REQUEST,
      AUTH.ACTIONS.SET_PASSWORD_REQUEST,
    ]),
    async submit(values) {
      const isOk = await this[AUTH.ACTIONS.SET_PASSWORD_REQUEST]({
        token: this.$route.params.token,
        password: values.password,
      })
      if (isOk) {
        await this.$router.push({ name: ROUTES.PASSWORD_CHANGED })
      }
    },
  },
}
</script>
