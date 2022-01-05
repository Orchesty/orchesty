<template>
  <v-card>
    <v-card-text>
      <hanaboso-logo />
    </v-card-text>
    <v-card-text class="text-center subtitle-1">
      {{ $t('setNewPassword.success.text') }}
      <router-link :to="{ name: ROUTES.LOGIN }">{{ $t('setNewPassword.success.link') }}</router-link>
    </v-card-text>
  </v-card>
</template>

<script>
import { ROUTES } from '../../../../router/routes'
import HanabosoLogo from '../../../commons/logo/Logo'

export default {
  name: 'PasswordChanged',
  components: { HanabosoLogo },
  data() {
    return {
      ROUTES: ROUTES,
    }
  },
}
</script>
