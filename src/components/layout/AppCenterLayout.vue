<template>
  <v-app id="app-page-center">
    <global-notifications />
    <v-main>
      <v-container class="fill-height" fluid>
        <v-row align="center" justify="center">
          <v-col cols="10" sm="8">
            <slot></slot>
          </v-col>
        </v-row>
      </v-container>
    </v-main>
    <page-footer />
  </v-app>
</template>

<script>
import PageFooter from './components/PageFooter'
import GlobalNotifications from '../commons/notifications/GlobalNotifications'

export default {
  name: 'PageCenterLayout',
  components: { GlobalNotifications, PageFooter },
}
</script>
