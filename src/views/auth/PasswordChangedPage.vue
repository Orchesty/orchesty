<template>
  <auth-layout>
    <v-card>
      <v-card-text>
        <full-logo />
      </v-card-text>
      <v-card-text class="text-center subtitle-2">
        {{ $t('setNewPassword.success.text') }}
        <router-link :to="{ name: ROUTES.LOGIN }">{{ $t('setNewPassword.success.link') }}</router-link>
      </v-card-text>
    </v-card>
  </auth-layout>
</template>

<script>
import { ROUTES } from '@/services/enums/routerEnums'
import AuthLayout from '@/components/layout/auth/AuthLayout'
import FullLogo from '@/components/commons/logo/FullLogo'

export default {
  name: 'PasswordChangedPage',
  components: { FullLogo, AuthLayout },
  data() {
    return {
      ROUTES: ROUTES,
    }
  },
}
</script>
