<template>
  <v-snackbar
    v-if="alert.type !== AlertType.Hidden"
    top
    :color="color"
    :timeout="alert.timeout"
    v-model="alertVisible"
  >
    {{ alert.message }}
    <template v-slot:action="{ attrs }">
      <v-btn color="white" text v-bind="attrs" @click="handleClose(alert.id)">
        {{ $t("button.close") }}
      </v-btn>
    </template>
  </v-snackbar>
</template>

<script lang="ts">
import { Prop, Vue, Component, Watch } from "vue-property-decorator";
import { alerts } from "../../../utils/alerts";
import { Alert as TAlert } from "../../../store/modules/alerts";
import { AlertType as _AlertType } from "../../../enums";

@Component
export default class Alert extends Vue {
  @Prop({ required: true, type: Object })
  private readonly alert!: TAlert;

  private AlertType = _AlertType;

  private alertVisible = true;

  get color(): string {
    switch (this.alert.type) {
      case this.AlertType.Error:
        return "error";
      case this.AlertType.Info:
        return "info";
      case this.AlertType.Success:
        return "success";
      default:
        return "info";
    }
  }

  @Watch("alertVisible")
  private handleClose(id: string) {
    alerts.removeAlert(id);
  }
}
</script>
