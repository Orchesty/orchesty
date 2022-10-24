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

@Component({
  components: { GlobalAlerts },
})
export default class App extends Vue {
  async created() {
    await loadApplicationsDetails()
  }
}
</script>

<style lang="scss"></style>
