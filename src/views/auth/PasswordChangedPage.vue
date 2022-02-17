<template>
  <auth-layout>
    <auth-split-layout disable-header>
      <template #form>
        <h2 class="mx-auto mb-2">Done, password changed!</h2>
        <p class="body-2 mx-auto">
          Your password has been changed successfully, you can
          <router-link :to="{ name: ROUTES.LOGIN }">login</router-link> now.
        </p>
        <div class="px-4">
          <app-button
            :height="44"
            :custom-style="{ width: '100%' }"
            button-title="Back to login page"
            :sending-title="$t('button.sending.sending')"
            :on-click="submit"
            :flat="false"
          />
        </div>
      </template>
    </auth-split-layout>
  </auth-layout>
</template>

<script>
import { ROUTES } from '@/services/enums/routerEnums'
import AuthLayout from '@/components/layout/auth/AuthLayout'
import AuthSplitLayout from '@/components/app/auth/layout/AuthSplitLayout'
import AppButton from '@/components/commons/button/AppButton'

export default {
  name: 'PasswordChangedPage',
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
