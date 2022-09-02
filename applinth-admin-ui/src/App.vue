<template>
  <v-app>
    <GlobalAlerts />
    <router-view />
  </v-app>
</template>

<script lang="ts">
import { Component, Vue } from "vue-property-decorator";
import GlobalAlerts from "./components/commons/alerts/GlobalAlerts.vue";

@Component({
  components: { GlobalAlerts },
})
export default class App extends Vue {}
</script>

<style lang="scss"></style>
