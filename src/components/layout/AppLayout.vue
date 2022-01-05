<template>
  <v-app>
    <global-notifications />

    <navigation />

    <v-main>
      <router-view :key="$route.path" />
    </v-main>

    <page-footer />
  </v-app>
</template>

<script>
import PageFooter from './components/PageFooter'
import GlobalNotifications from '../commons/notifications/GlobalNotifications'
import Navigation from './components/Navigation'

export default {
  name: 'AppLayout',
  components: { Navigation, GlobalNotifications, PageFooter },
}
</script>
