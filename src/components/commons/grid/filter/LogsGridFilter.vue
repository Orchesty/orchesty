<template>
  <v-row dense>
    <v-col key="fulltext" cols="12" sm="6" md="2">
      <app-input
        v-model="fullTextSearch"
        hide-details
        clearable
        label="Fulltext Search"
      />
    </v-col>
    <v-col key="timeMargin" cols="12" sm="6" md="2">
      <app-input
        v-model.number="timeMargin"
        :disabled="!fullTextSearch"
        hide-details
        type="number"
        label="Time Margin"
      />
    </v-col>
    <v-col class="my-auto">
      <app-button
        :button-title="$t('button.filter')"
        :on-click="fetchGrid"
        class="py-5"
      />
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
  name: "LogsGridFilter",
  components: { AppButton, AppInput },
  data() {
    return {
      fullTextSearch: "",
      timeMargin: 0,
      logsFilter: [
        [
          {
            column: "time_margin",
            operator: OPERATOR.EQUAL,
            value: 0,
          },
        ],
      ],
    }
  },
  methods: {
    fetchGrid() {
      this.$emit("fetchGrid", {
        filter: this.logsFilter,
        search: this.fullTextSearch,
      })
    },
    resetFilter() {
      this.fullTextSearch = ""
      this.timeMargin = 0

      this.$emit("fetchGrid")
    },
  },
  watch: {
    timeMargin: {
      handler(timeMargin) {
        this.logsFilter[0][0].value = timeMargin
      },
    },
  },
}
</script>

<style scoped></style>
