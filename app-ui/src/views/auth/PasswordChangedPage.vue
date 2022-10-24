<template>
  <auth-layout>
    <auth-split-layout>
      <template #heading> {{ $t("auth.page.passwordChanged.title") }}</template>
      <template #form>
        <p>
          <i18n
            path="auth.page.passwordChanged.body"
            tag="span"
            for="auth.page.passwordChanged.login"
          >
            <router-link :to="{ name: ROUTES.LOGIN }">
              {{ $t("auth.page.passwordChanged.login") }}</router-link
            >
          </i18n>
        </p>
        <div class="text-right">
          <app-button :button-title="$t('button.login')" :on-click="submit" />
        </div>
      </template>
    </auth-split-layout>
  </auth-layout>
</template>

<script>
import { ROUTES } from "@/services/enums/routerEnums"
import AuthLayout from "@/components/layout/auth/AuthLayout"
import AuthSplitLayout from "@/components/app/auth/layout/AuthSplitLayout"
import AppButton from "@/components/commons/button/AppButton"

export default {
  name: "PasswordChangedPage",
  components: { AppButton, AuthSplitLayout, AuthLayout },
  data() {
    return {
      ROUTES: ROUTES,
    }
  },
  methods: {
    async submit() {
      await this.$router.push({ name: ROUTES.LOGIN })
    },
  },
}
</script>
