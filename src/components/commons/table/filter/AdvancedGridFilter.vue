<template>
  <div>
    <div v-for="(andIndexItem, andIndex) in innerFilter" :key="`andIndex-${andIndex}`" class="mb-4">
      <div v-for="(orIndexItem, orIndex) in andIndexItem" :key="orIndex">
        <div class="d-flex">
          <v-container class="pa-0 mr-2">
            <v-row>
              <v-col cols="12" md="3">
                <select-box-input
                  :rules="{ required: true, oneOf: filterableColumns.map((item) => item.value) }"
                  :value="innerFilter[andIndex][orIndex].column || filterableColumns[0].value"
                  :items="filterableColumns"
                  :label="$t('dataGrid.advanced.column')"
                  :disabled="andIndexItem.length > 1"
                  :on-change="(val) => onChangeFilteredColumn(andIndex, val)"
                />
              </v-col>

              <v-col cols="12" md="3">
                <select-box-input
                  :rules="{
                    required: true,
                    oneOf: getOperatorsByFilterType(getFilterByColumn(orIndexItem.column).type),
                  }"
                  :items="getOperatorsByFilterType(getFilterByColumn(orIndexItem.column).type)"
                  :value="innerFilter[andIndex][orIndex].operator || OPERATOR.EQUAL"
                  :label="$t('dataGrid.advanced.operator')"
                  :on-change="(val) => onOperatorChange(andIndex, orIndex, val)"
                />
              </v-col>

              <!-- VALUES -->
              <v-col v-if="getFilterByColumn(orIndexItem.column).type === FILTER_TYPE.TEXT" cols="12" md="3">
                <text-input
                  :rules="{ required: true }"
                  :value="orIndexItem.value[0] || null"
                  :label="$t('dataGrid.advanced.value')"
                  :on-change="(val) => onValueChange(andIndex, orIndex, orIndexItem.column, val)"
                />
              </v-col>

              <v-col v-if="getFilterByColumn(orIndexItem.column).type === FILTER_TYPE.NUMBER" cols="12" md="3">
                <number-input
                  :rules="{ required: true }"
                  :value="orIndexItem.value[0] || null"
                  :label="$t('dataGrid.advanced.value')"
                  :on-change="(val) => onValueChange(andIndex, orIndex, orIndexItem.column, val)"
                />
              </v-col>

              <v-col v-if="getFilterByColumn(orIndexItem.column).type === FILTER_TYPE.ENUMS" cols="12" md="3">
                <select-box-input
                  :rules="{ required: true }"
                  :value="orIndexItem.value[0] || null"
                  :items="getFilterByColumn(orIndexItem.column).items"
                  :label="$t('dataGrid.advanced.value')"
                  :on-change="(val) => onValueChange(andIndex, orIndex, orIndexItem.column, val)"
                />
              </v-col>

              <v-col v-if="getFilterByColumn(orIndexItem.column).type === FILTER_TYPE.MULTIPLE_ENUMS" cols="12" md="3">
                <multiple-select-box-input
                  :rules="{ required: true }"
                  :value="orIndexItem.value || []"
                  :items="getFilterByColumn(orIndexItem.column).items"
                  :label="$t('dataGrid.advanced.value')"
                  :on-change="(val) => onValueChange(andIndex, orIndex, orIndexItem.column, val)"
                />
              </v-col>

              <v-col v-if="getFilterByColumn(orIndexItem.column).type === FILTER_TYPE.AUTO_COMPLETE" cols="12" md="3">
                <auto-complete-input
                  :rules="{ required: true }"
                  :value="orIndexItem.value || []"
                  :column="orIndexItem.column"
                  :request-id="getFilterByColumn(orIndexItem.column).requestId"
                  :on-search="getFilterByColumn(orIndexItem.column).request"
                  :label="$t('dataGrid.advanced.value')"
                  :on-change="(val) => onValueChange(andIndex, orIndex, orIndexItem.column, val)"
                />
              </v-col>

              <v-col v-if="getFilterByColumn(orIndexItem.column).type === FILTER_TYPE.DATETIME" cols="12" md="3">
                <date-time-input
                  :rules="{ required: true }"
                  :value="orIndexItem.value[0] || null"
                  :label="$t('dataGrid.advanced.value')"
                  :on-change="(val) => onValueChange(andIndex, orIndex, orIndexItem.column, val)"
                />
              </v-col>

              <v-col v-if="getFilterByColumn(orIndexItem.column).type === FILTER_TYPE.BOOLEAN" cols="12" md="3">
                <boolean-input
                  :rules="{ required: true }"
                  :value="orIndexItem.value[0] || null"
                  :label="$t('dataGrid.advanced.value')"
                  :on-change="(val) => onValueChange(andIndex, orIndex, orIndexItem.column, val)"
                />
              </v-col>

              <v-col
                v-if="getFilterByColumn(orIndexItem.column).type === FILTER_TYPE.DATE_TIME_BETWEEN"
                cols="12"
                md="6"
              >
                <date-time-between-input
                  :rules="{ required: true }"
                  :value="orIndexItem.value || []"
                  :label="$t('dataGrid.advanced.value')"
                  :on-change="(val) => onValueChange(andIndex, orIndex, orIndexItem.column, val)"
                />
              </v-col>

              <v-col v-if="getFilterByColumn(orIndexItem.column).type === FILTER_TYPE.DATE" cols="12" md="3">
                <date-input
                  :rules="{ required: true }"
                  :value="orIndexItem.value[0] || null"
                  :label="$t('dataGrid.advanced.value')"
                  :on-change="(val) => onValueChange(andIndex, orIndex, orIndexItem.column, val)"
                />
              </v-col>

              <v-col v-if="getFilterByColumn(orIndexItem.column).type === FILTER_TYPE.DATE_BETWEEN" cols="12" md="6">
                <date-between-input
                  :rules="{ required: true }"
                  :value="orIndexItem.value || []"
                  :label="$t('dataGrid.advanced.value')"
                  :on-change="(val) => onValueChange(andIndex, orIndex, orIndexItem.column, val)"
                />
              </v-col>
            </v-row>
          </v-container>

          <div style="flex-grow: 0 !important; white-space: nowrap; min-width: 100px">
            <v-col class="text-right">
              <v-btn
                v-if="andIndexItem.length === orIndex + 1"
                class="ma-0"
                icon
                @click="addOrIndex(andIndex, orIndex)"
              >
                {{ $t('dataGrid.advanced.or') }}
              </v-btn>
              <v-btn class="ma-0" icon @click="deleteOrIndex(andIndex, orIndex)">
                <v-icon>delete</v-icon>
              </v-btn>
            </v-col>
          </div>
        </div>
      </div>
    </div>

    <v-btn @click="addAndIndex">
      {{ $t('dataGrid.advanced.and') }}
    </v-btn>
  </div>
</template>

<script>
import { removeFilter, upsertFilter } from '@/services/utils/gridFilters'
import { FILTER, FILTER_TYPE, OPERATOR } from '@/services/enums/gridEnums'
import SelectBoxInput from './inputs/SelectBoxInput'
import TextInput from './inputs/TextInput'
import NumberInput from './inputs/NumberInput'
import AutoCompleteInput from './inputs/AutoCompleteInput'
import DateTimeInput from './inputs/DateTimeInput'
import BooleanInput from './inputs/BooleanInput'
import DateTimeBetweenInput from './inputs/DateTimeBetweenInput'
import DateInput from './inputs/DateInput'
import MultipleSelectBoxInput from './inputs/MultipleSelectBoxInput'
import DateBetweenInput from './inputs/DateBetweenInput'

export default {
  name: 'AdvancedGridFilter',
  components: {
    MultipleSelectBoxInput,
    SelectBoxInput,
    TextInput,
    NumberInput,
    AutoCompleteInput,
    DateTimeInput,
    BooleanInput,
    DateTimeBetweenInput,
    DateInput,
    DateBetweenInput,
  },
  props: {
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
      FILTER_TYPE: FILTER_TYPE,
      OPERATOR: OPERATOR,
      innerFilter: [],
    }
  },
  computed: {
    filterableColumns() {
      return this.headers
        .filter((column) => column.filter)
        .map((column) => ({ text: this.$t(column.text), value: column.value }))
    },
  },
  watch: {
    filter: {
      handler() {
        if (this.isAdvancedFilter(this.filterMeta)) {
          this.innerFilter = this.filter
        }
      },
      deep: true,
    },
    innerFilter: {
      deep: true,
      handler() {
        console.log(this.innerFilter, 'CHILD')
        this.onChange(this.innerFilter, { type: FILTER.ADVANCED_FILTER })
      },
    },
    filterMeta(meta) {
      // reset filter if not ADVANCED_FILTER
      if (!meta || meta.type !== FILTER.ADVANCED_FILTER) {
        this.innerFilter = []
      }
    },
  },
  methods: {
    getFilterByColumn(column) {
      let headerItem = this.headers.find((item) => item.value === column)

      if (headerItem && headerItem.filter) {
        return headerItem.filter
      }
    },
    getOperatorsByColumn(column) {
      return this.getOperatorsByFilterType(this.getFilterByColumn(column).type)
        ? this.getOperatorsByFilterType(this.getFilterByColumn(column).type)
        : false
    },
    getOperatorsByFilterType(filterType) {
      switch (filterType) {
        case FILTER_TYPE.TEXT:
          return [
            OPERATOR.EQUAL,
            OPERATOR.NOT_EQUAL,
            OPERATOR.LIKE,
            OPERATOR.STARTS_WITH,
            OPERATOR.ENDS_WITH,
            OPERATOR.EMPTY,
            OPERATOR.NEMPTY,
          ]
        case FILTER_TYPE.NUMBER:
        case FILTER_TYPE.DATETIME:
        case FILTER_TYPE.DATE:
          return [
            OPERATOR.EQUAL,
            OPERATOR.NOT_EQUAL,
            OPERATOR.GREATER_THAN,
            OPERATOR.LESS_THAN,
            OPERATOR.GREATER_THAN_OR_EQUAL,
            OPERATOR.LESS_THAN_OR_EQUAL,
          ]
        case FILTER_TYPE.ENUMS:
        case FILTER_TYPE.MULTIPLE_ENUMS:
        case FILTER_TYPE.AUTO_COMPLETE:
          return [OPERATOR.IN, OPERATOR.NIN]
        case FILTER_TYPE.BOOLEAN:
          return [OPERATOR.EQUAL]
        case FILTER_TYPE.DATE_TIME_BETWEEN:
        case FILTER_TYPE.DATE_BETWEEN:
          return [OPERATOR.BETWEEN]
        default:
          return Object.values(OPERATOR)
      }
    },
    addAndIndex() {
      const column = this.filterableColumns[0].value || null

      if (!column) {
        return
      }

      this.innerFilter = upsertFilter(this.innerFilter, this.innerFilter.length, 0, {
        column,
        value: [],
        operator: this.getOperatorsByColumn(column)[0] || OPERATOR.EQUAL,
      })
    },
    addOrIndex(andIndex, orIndex) {
      const column = this.innerFilter[andIndex][0].column

      this.innerFilter = upsertFilter(this.innerFilter, andIndex, orIndex + 1, {
        column: column,
        value: [],
        operator: this.getOperatorsByColumn(column)[0] || OPERATOR.EQUAL,
      })
    },
    deleteOrIndex(andIndex, orIndex) {
      removeFilter(this.innerFilter, andIndex, orIndex)
    },
    onChangeFilteredColumn(andIndex, column) {
      this.innerFilter[andIndex] = this.innerFilter[andIndex].map(() => {
        return {
          column,
          operator: this.getOperatorsByColumn(column)[0] || OPERATOR.EQUAL,
          value: [],
        }
      })

      this.innerFilter = [...this.innerFilter]
    },
    onOperatorChange(andIndex, orIndex, operator) {
      upsertFilter(this.innerFilter, andIndex, orIndex, {
        column: this.innerFilter[andIndex][orIndex].column,
        operator: operator,
        value: this.innerFilter[andIndex][orIndex].value,
      })
    },
    onValueChange(andIndex, orIndex, column, value) {
      upsertFilter(this.innerFilter, andIndex, orIndex, {
        column: column,
        operator: this.innerFilter[andIndex][orIndex].operator,
        value: Array.isArray(value) ? value : [value],
      })
    },
    isAdvancedFilter(meta) {
      return meta && (meta.type === FILTER.ADVANCED_FILTER || meta.type === undefined)
    },
  },
}
</script>
