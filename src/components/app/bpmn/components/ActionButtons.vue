<template>
  <div>
    <tooltip>
      <template #activator="{ on, attrs }">
        <app-button :attrs="attrs" :on="on" icon :on-click="routeBack">
          <template #icon>
            <app-icon> mdi-arrow-left-circle </app-icon>
          </template>
        </app-button>
      </template>
      <template #tooltip> {{ $t("button.back") }} </template>
    </tooltip>

    <tooltip>
      <template #activator="{ on, attrs }">
        <app-button
          :attrs="attrs"
          :on="on"
          icon
          class="ml-1"
          :on-click="
            () => {
              saveDiagram()
            }
          "
        >
          <template #icon>
            <app-icon> mdi-content-save </app-icon>
          </template>
        </app-button>
      </template>
      <template #tooltip> {{ $t("button.save") }} </template>
    </tooltip>

    <v-menu offset-y>
      <template #activator="{ on, attrs }">
        <app-button
          icon
          :disabled="isSending"
          class="ml-1"
          :attrs="attrs"
          :on="on"
        >
          <template #icon>
            <app-icon>mdi-dots-vertical</app-icon>
          </template>
        </app-button>
      </template>
      <v-list dense>
        <v-list-item link @click="$refs.import.click()">
          <v-list-item-title class="d-flex justify-space-between align-center">
            <span class="mr-2">{{ $t("contextMenu.topology.import") }}</span>
            <app-icon dense>mdi-import</app-icon>
          </v-list-item-title>
          <input
            id="import"
            ref="import"
            type="file"
            hidden
            @change="
              (e) => {
                setFileInput(e)
              }
            "
          />
        </v-list-item>

        <v-list-item link @click="exportDiagram()">
          <v-list-item-title class="d-flex justify-space-between align-center">
            <span class="mr-2">{{ $t("button.export") }}</span>
            <app-icon dense>mdi-export</app-icon>
          </v-list-item-title>
        </v-list-item>
      </v-list>
    </v-menu>
  </div>
</template>

<script>
import Tooltip from "@/components/commons/Tooltip"
import AppButton from "@/components/commons/button/AppButton"
import AppIcon from "@/components/commons/icon/AppIcon"
export default {
  name: "ActionButtons",
  components: { AppIcon, AppButton, Tooltip },
  props: {
    saveDiagram: {
      type: Function,
      required: true,
    },
    setFileInput: {
      type: Function,
      required: true,
    },
    exportDiagram: {
      type: Function,
      required: true,
    },
    routeBack: {
      type: Function,
      required: true,
    },
    isSending: {
      type: Boolean,
      required: true,
    },
  },
}
</script>

<style scoped></style>
