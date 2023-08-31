<template>
  <auth-layout>
    <auth-split-layout>
      <template #heading> {{ $t("page.text.passwordChangedTitle") }}</template>
      <template #form>
        <p>
          <i18n
            path="page.text.passwordChangedBody"
            tag="span"
            for="page.text.passwordChangedLogin"
          >
            <router-link :to="{ name: ROUTES.LOGIN }">
              {{ $t("page.text.passwordChangedLogin") }}</router-link
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
