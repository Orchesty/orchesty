<template>
  <v-row dense>
    <v-col key="fulltext" cols="12" sm="6" md="2">
      <app-input v-model="fullTextSearch" hide-details clearable label="Fulltext Search" />
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
      <app-button :button-title="$t('dataGrid.runFilter')" :on-click="sendFilter" class="py-5" />
      <app-button flat icon :on-click="resetFilter">
        <template #icon>
          <v-icon> mdi-close </v-icon>
        </template>
      </app-button>
    </v-col>
  </v-row>
</template>

<script>
import AppInput from '@/components/commons/input/AppInput'
import AppButton from '@/components/commons/button/AppButton'
import { OPERATOR } from '@/services/enums/gridEnums'
export default {
  name: 'LogsGridSimpleFilter',
  components: { AppButton, AppInput },
  data() {
    return {
      fullTextSearch: null,
      timeMargin: 0,
    }
  },
  methods: {
    sendFilter() {
      const filter = [
        [
          {
            column: 'time_margin',
            operator: OPERATOR.EQUAL,
            value: this.timeMargin,
          },
        ],
      ]
      this.$emit('fetchGrid', { filter, search: this.fullTextSearch })
    },
    resetFilter() {
      this.fullTextSearch = null
      this.timeMargin = 0

      this.$emit('fetchGrid')
    },
  },
}
</script>

<style scoped></style>
