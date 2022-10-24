<template>
  <v-row dense>
    <v-col cols="2">
      <app-input v-model="nodeName" hide-details clearable label="Node" />
    </v-col>

    <v-col class="my-auto">
      <app-button :on-click="sendFilter" :button-title="$t('button.filter')" />
      <app-button flat icon :on-click="resetFilter">
        <template #icon>
          <v-icon> mdi-close </v-icon>
        </template>
      </app-button>
    </v-col>
  </v-row>
</template>

<script>
import AppInput from "@/components/commons/input/AppInput"
import AppButton from "@/components/commons/button/AppButton"
import { OPERATOR } from "@/services/enums/gridEnums"
export default {
  name: "UserTaskGridSimpleFilter",
  components: { AppButton, AppInput },
  data() {
    return {
      nodeName: null,
    }
  },
  methods: {
    sendFilter() {
      const filter = [
        [
          {
            column: "nodeName",
            operator: OPERATOR.LIKE,
            value: this.nodeName,
          },
        ],
      ]
      this.$emit("fetchGrid", { filter })
    },
    resetFilter() {
      this.nodeName = null

      this.$emit("fetchGrid")
    },
  },
}
</script>

<style scoped></style>
