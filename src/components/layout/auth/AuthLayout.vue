<template>
  <v-app>
    <global-notifications />
    <v-main>
      <v-container fill-height fluid>
        <v-row align="center" justify="center">
          <v-col cols="10" sm="6">
            <slot></slot>
          </v-col>
        </v-row>
      </v-container>
    </v-main>
    <page-footer />
  </v-app>
</template>

<script>
import PageFooter from '../content/ContentFooter'
import GlobalNotifications from '../../commons/notifications/GlobalNotifications'

export default {
  name: 'AuthLayout',
  components: { GlobalNotifications, PageFooter },
}
</script>
