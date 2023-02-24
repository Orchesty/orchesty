<template>
  <v-app>
    <global-notifications />
    <v-main>
      <v-container fill-height fluid>
        <v-row align="center" justify="center">
          <v-col xl="5" lg="7" md="6" sm="8" cols="12">
            <slot></slot>
          </v-col>
        </v-row>
      </v-container>
    </v-main>
    <page-footer />
  </v-app>
</template>

<script>
import PageFooter from "../content/ContentFooter"
import GlobalNotifications from "../../commons/notifications/GlobalNotifications"

export default {
  name: "AuthLayout",
  components: { GlobalNotifications, PageFooter },
}
</script>
