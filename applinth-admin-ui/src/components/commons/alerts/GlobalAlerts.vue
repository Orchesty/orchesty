<template>
  <div v-if="alertsData.length">
    <Alert v-for="alert in alertsData" :alert="alert" :key="alert.id" />
  </div>
</template>

<script lang="ts">
import Alert from "./Alert.vue";
import { Component, Vue } from "vue-property-decorator";
import { AlertsState } from "../../../store/modules/alerts";
import { alertsNamespace } from "../../../store/modules/alerts/types";
import { State } from "vuex-class";

@Component({
  components: { Alert },
})
export default class GlobalAlerts extends Vue {
  @State(alertsNamespace)
  private alertsData!: AlertsState;
}
</script>
