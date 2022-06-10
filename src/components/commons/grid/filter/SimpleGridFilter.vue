<template>
  <validation-observer tag="form" @keydown.enter="$emit('sendFilter')">
    <v-row dense class="mt-3 mb-2">
      <template v-if="simpleFilterEnum === SIMPLE_FILTER.TRASH">
        <v-col cols="2">
          <app-input
            v-model="trashFilterValues.topologyName"
            prepend-icon="account_tree"
            hide-details
            dense
            outlined
            clearable
            label="Topology"
          />
        </v-col>
        <v-col cols="2">
          <app-input
            v-model="trashFilterValues.nodeName"
            hide-details
            dense
            outlined
            clearable
            label="Node"
            prepend-icon="mdi-directions-fork"
          />
        </v-col>
        <v-col cols="2">
          <app-input
            v-model="trashFilterValues.correlationId"
            hide-details
            dense
            outlined
            clearable
            label="Correlation ID"
            prepend-icon="mdi-directions-fork"
          />
        </v-col>
        <v-col cols="2">
          <app-input
            v-model="trashFilterValues.native"
            prepend-icon="mdi-tools"
            hide-details
            dense
            outlined
            clearable
            label="Custom"
            placeholder="key:value"
          />
        </v-col>
        <v-col class="my-auto">
          <app-button :on-click="() => $emit('sendFilter')" :button-title="$t('dataGrid.runFilter')" />
          <slot name="resetClearButtons" :on-clear-button="() => {}" />
        </v-col>
      </template>

      <template v-if="simpleFilterEnum === SIMPLE_FILTER.LOGS">
        <v-col key="fulltext" cols="12" sm="6" md="2">
          <app-input
            v-model="logsFilterValues.fullTextSearch"
            prepend-icon="search"
            hide-details
            dense
            outlined
            clearable
            label="Fulltext Search"
          />
        </v-col>
        <v-col key="timeMargin" cols="12" sm="6" md="2">
          <app-input
            v-model.number="logsFilterValues.timeMargin"
            prepend-icon="mdi-clock"
            :disabled="!logsFilterValues.fullTextSearch"
            hide-details
            dense
            outlined
            clearable
            type="number"
            label="Time Margin"
            numbers-only
          />
        </v-col>
        <v-col class="my-auto">
          <app-button :button-title="$t('dataGrid.runFilter')" :on-click="() => $emit('sendFilter')" class="py-5" />
          <slot name="resetClearButtons" :on-clear-button="() => {}" />
        </v-col>
      </template>
    </v-row>
  </validation-observer>
</template>

<script>
import { FILTER_TYPE, OPERATOR } from '@/services/enums/gridEnums'
import { SIMPLE_FILTER } from '@/services/enums/dataGridFilterEnums'
import AppInput from '@/components/commons/input/AppInput'
import AppButton from '@/components/commons/button/AppButton'

export default {
  name: 'SimpleGridFilter',
  components: {
    AppButton,
    AppInput,
  },
  props: {
    simpleFilterEnum: {
      type: String,
      required: true,
    },
    headers: {
      type: Array,
      required: true,
    },
    filter: {
      type: Array,
      required: true,
    },
    filterMeta: {
      type: Object,
      required: true,
    },
    onChange: {
      type: Function,
      required: true,
    },
  },
  data() {
    return {
      SIMPLE_FILTER,
      FILTER_TYPE,
      OPERATOR,
      logsFilter: [
        [
          {
            column: 'time_margin',
            operator: OPERATOR.EQUAL,
            value: 0,
          },
        ],
      ],
      trashFilter: null,
      logsFilterValues: {
        fullTextSearch: '',
        timeMargin: 0,
      },
      trashFilterValues: {
        topologyName: '',
        correlation: '',
        nodeName: '',
        native: '',
      },
    }
  },
  watch: {
    logsFilterValues: {
      deep: true,
      handler(logsFilterValues) {
        this.logsFilter = [
          [
            {
              column: 'time_margin',
              operator: OPERATOR.EQUAL,
              value: logsFilterValues.timeMargin,
            },
          ],
        ]
      },
    },
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
