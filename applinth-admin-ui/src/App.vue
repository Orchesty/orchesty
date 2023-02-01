<template>
  <v-app>
    <GlobalAlerts />
    <router-view />
  </v-app>
</template>

<script lang="ts">
import { Component, Vue } from "vue-property-decorator"
import GlobalAlerts from "./components/commons/alerts/GlobalAlerts.vue"
import { loadApplicationsDetails } from "@/utils"
import { fetchLastBillingHistoryDateGenerated } from "@/service/billingService"
import { LocalStorage } from "@/enums"
import { Getter } from "vuex-class"
import { AuthGetters, authNamespace, User } from "@/store/modules/auth"

@Component({
  components: { GlobalAlerts },
})
export default class App extends Vue {
  @Getter(`${authNamespace}/${AuthGetters.GetUser}`)
  currentUser!: User

  async created() {
    await loadApplicationsDetails()

    try {
      if (this.currentUser) {
        const date = await fetchLastBillingHistoryDateGenerated()
        localStorage.setItem(LocalStorage.priceCalculatedAt, date)
      }
    } catch (e) {
      //
    }
  }
}
</script>

<style lang="scss">
@import "assets/scss/main";
</style>
