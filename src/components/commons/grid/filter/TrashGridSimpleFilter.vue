<template>
  <v-row dense>
    <v-col cols="2">
      <app-input v-model="trashFilterValues.topologyName" hide-details clearable label="Topology" />
    </v-col>
    <v-col cols="2">
      <app-input v-model="trashFilterValues.nodeName" hide-details clearable label="Node" />
    </v-col>
    <v-col cols="2">
      <app-input v-model="trashFilterValues.correlationId" hide-details clearable label="Correlation ID" />
    </v-col>
    <v-col cols="2">
      <app-input v-model="trashFilterValues.native" hide-details clearable label="Custom" placeholder="key:value" />
    </v-col>
    <v-col class="my-auto">
      <app-button :on-click="sendFilter" :button-title="$t('dataGrid.runFilter')" />
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
  name: 'TrashGridSimpleFilter',
  components: { AppButton, AppInput },
  data() {
    return {
      trashFilter: null,
      trashFilterValues: {
        topologyName: '',
        correlationId: '',
        nodeName: '',
        native: '',
      },
    }
  },
  methods: {
    sendFilter() {
      this.$emit('fetchGrid', { filter: this.trashFilter })
    },
    resetFilter() {
      this.trashFilterValues = {
        topologyName: '',
        correlationId: '',
        nodeName: '',
        native: '',
      }

      this.$emit('fetchGrid')
    },
  },
  watch: {
    trashFilterValues: {
      deep: true,
      handler(val) {
        let filter = []
        let keys = Object.keys(val).filter((key) => {
          return val[key]
        })
        keys.forEach((key) => {
          if (key.includes('topologyName') || key.includes('nodeName')) {
            filter.push([
              { column: key, operator: OPERATOR.LIKE, value: val[key] },
              { column: key.replace('Name', 'Id'), operator: OPERATOR.LIKE, value: val[key] },
            ])
          } else if (key.includes('native')) {
            filter.push([{ column: val[key].split(':')[0], operator: OPERATOR.LIKE, value: val[key].split(':')[1] }])
          } else {
            filter.push([{ column: key, operator: OPERATOR.LIKE, value: val[key] }])
          }
        })
        this.trashFilter = filter
      },
    },
  },
}
</script>

<style scoped></style>
