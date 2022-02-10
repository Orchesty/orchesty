<template>
  <validation-observer tag="form" @keydown.enter="$emit('sendFilter')">
    <v-row dense class="mt-1">
      <template v-for="(item, key) in items">
        <v-col v-if="item.type && item.type === FILTER_TYPE.TEXT" :key="item.column" cols="12" sm="6" md="3">
          <text-input
            :key="item.value && item.value[0] ? item.column : `${item.column}_rerender`"
            :label="$t(item.text)"
            :column="item.column"
            :value="item.value && item.value[0] ? item.value[0] : ''"
            :on-change="(value) => onChangeInput(key, item.column, value, item.operator || undefined)"
            clearable
          />
        </v-col>
        <v-col v-if="item.type && item.type === FILTER_TYPE.NUMBER" :key="item.column" cols="12" sm="6" md="3">
          <number-input
            :label="$t(item.text)"
            :column="item.column"
            :value="item.value && item.value[0] ? item.value[0] : ''"
            :on-change="(value) => onChangeInput(key, item.column, value, item.operator || undefined)"
            clearable
          />
        </v-col>
        <v-col v-if="item.type && item.type === FILTER_TYPE.ENUMS" :key="item.column" cols="12" sm="6" md="3">
          <select-box-input
            :label="$t(item.text)"
            :column="item.column"
            :value="item.value && item.value[0] ? item.value[0] : ''"
            :items="item.items"
            :on-change="(value) => onChangeInput(key, item.column, value, item.operator || undefined)"
            clearable
          />
        </v-col>
        <v-col v-if="item.type && item.type === FILTER_TYPE.MULTIPLE_ENUMS" :key="item.column" cols="12" sm="6" md="3">
          <multiple-select-box-input
            :label="$t(item.text)"
            :column="item.column"
            :value="item.value ? item.value : []"
            :items="item.items"
            :on-change="(value) => onChangeInput(key, item.column, value, OPERATOR.IN)"
            clearable
          />
        </v-col>
        <v-col v-if="item.type && item.type === FILTER_TYPE.AUTO_COMPLETE" :key="item.column" cols="12" sm="6" md="3">
          <auto-complete-input
            :label="$t(item.text)"
            :column="item.column"
            :value="item.value ? item.value : []"
            :on-search="item.request"
            :request-id="item.requestId"
            :multiple="item.multiple"
            :request-on-focus="item.requestOnFocus"
            :on-change="
              (value) =>
                onChangeInput(
                  key,
                  item.column,
                  value,
                  typeof item.multiple == 'undefined' || item.multiple ? OPERATOR.IN : OPERATOR.EQUAL
                )
            "
          />
        </v-col>
        <v-col v-if="item.type && item.type === FILTER_TYPE.DATETIME" :key="item.column" cols="12" sm="6" md="3">
          <date-time-input
            :label="$t(item.text)"
            :column="item.column"
            :value="item.value && item.value[0] ? item.value[0] : ''"
            :on-change="(value) => onChangeInput(key, item.column, value)"
          />
        </v-col>
        <v-col v-if="item.type && item.type === FILTER_TYPE.BOOLEAN" :key="item.column" cols="12" sm="6" md="3">
          <boolean-input
            :label="$t(item.text)"
            :column="item.column"
            :value="item.value && item.value[0] ? item.value[0] : ''"
            :on-change="(value) => onChangeInput(key, item.column, value)"
            allow-all-item
          />
        </v-col>
        <v-col
          v-if="item.type && item.type === FILTER_TYPE.DATE_TIME_BETWEEN"
          :key="item.column"
          cols="12"
          sm="6"
          md="6"
        >
          <date-time-between-input
            :label="$t(item.text)"
            :column="item.column"
            :value="item.value ? item.value : []"
            :on-change="(value) => onChangeInput(key, item.column, value, OPERATOR.BETWEEN)"
          />
        </v-col>
        <v-col v-if="item.type && item.type === FILTER_TYPE.DATE" :key="item.column" cols="12" sm="6" md="3">
          <date-input
            :label="$t(item.text)"
            :column="item.column"
            :value="item.value && item.value[0] ? item.value[0] : ''"
            :on-change="(value) => onChangeInput(key, item.column, value)"
          />
        </v-col>
        <v-col v-if="item.type && item.type === FILTER_TYPE.DATE_BETWEEN" :key="item.column" cols="12" sm="6" md="3">
          <date-between-input
            :label="$t(item.text)"
            :column="item.column"
            :value="item.value ? item.value : []"
            :on-change="(value) => onChangeInput(key, item.column, value, OPERATOR.BETWEEN)"
          />
        </v-col>
      </template>
      <v-col v-if="showFullTextSearch" key="fulltext" cols="12" sm="6" md="3">
        <v-text-field v-model="fullTextSearch" hide-details dense outlined clearable label="Fulltext Search" />
      </v-col>
      <v-col v-if="showFullTextSearch" key="timeMargin" cols="12" sm="6" md="3">
        <v-text-field
          v-model.number="timeMargin"
          :disabled="!fullTextSearch"
          hide-details
          dense
          outlined
          clearable
          label="Time Margin"
          @keypress="isNumber($event)"
        />
      </v-col>
      <v-col v-if="showFullTextSearch" class="my-auto">
        <v-btn color="primary" class="py-5" @click="$emit('sendFilter')">
          {{ $t('dataGrid.runFilter') }}
        </v-btn>
        <slot name="resetClearButtons" :on-clear-button="() => {}" />
      </v-col>
    </v-row>
  </validation-observer>
</template>

<script>
import { FILTER, FILTER_TYPE, OPERATOR } from '@/services/enums/gridEnums'
import { clearFilter, upsertFilter } from '@/services/utils/gridFilters'
import TextInput from './inputs/TextInput'
import NumberInput from './inputs/NumberInput'
import SelectBoxInput from './inputs/SelectBoxInput'
import AutoCompleteInput from './inputs/AutoCompleteInput'
import DateTimeInput from './inputs/DateTimeInput'
import BooleanInput from './inputs/BooleanInput'
import DateTimeBetweenInput from './inputs/DateTimeBetweenInput'
import MultipleSelectBoxInput from './inputs/MultipleSelectBoxInput'
import DateInput from './inputs/DateInput'
import DateBetweenInput from './inputs/DateBetweenInput'

export default {
  name: 'SimpleGridFilter',
  components: {
    MultipleSelectBoxInput,
    TextInput,
    NumberInput,
    SelectBoxInput,
    AutoCompleteInput,
    DateTimeInput,
    BooleanInput,
    DateTimeBetweenInput,
    DateInput,
    DateBetweenInput,
  },
  props: {
    showFullTextSearch: {
      type: Boolean,
      required: true,
    },
    value: {
      type: Object,
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
      FILTER_TYPE: FILTER_TYPE,
      OPERATOR: OPERATOR,
      items: this.initItems(this.filter, this.headers, this.filterMeta),
      innerFilter: this.initFilter(this.filter, this.headers, this.filterMeta),
      fullTextSearch: '',
      timeMargin: 0,
    }
  },
  watch: {
    value: {
      deep: true,
      handler(val) {
        this.fullTextSearch = val.fullTextSearch
        this.timeMargin = val.timeMargin
      },
    },
    fullTextSearch() {
      if (!this.fullTextSearch) {
        this.timeMargin = 0
      }
      this.$emit('input', { fullTextSearch: this.fullTextSearch, timeMargin: this.timeMargin })
    },
    timeMargin() {
      this.$emit('input', { fullTextSearch: this.fullTextSearch, timeMargin: this.timeMargin })
    },
    filterMeta(meta) {
      // reset filter if not SIMPLE_FILTER
      if (!meta || meta.type !== FILTER.SIMPLE_FILTER || meta.type !== FILTER.QUICK_FILTER) {
        this.items = this.initItems([], this.headers, this.filterMeta)
        this.innerFilter = []
      }
    },
    filter() {
      if (this.isSimpleFilter(this.filterMeta)) {
        this.items = this.initItems(this.filter, this.headers, this.filterMeta)
        this.innerFilter = this.initFilter(this.filter, this.headers, this.filterMeta)
      }
    },
  },
  created() {
    if (this.isSimpleFilter(this.filterMeta)) {
      this.onChange(clearFilter(this.innerFilter), { type: FILTER.SIMPLE_FILTER })
    }
  },
  methods: {
    isNumber($event) {
      let keyCode = $event.keyCode ? $event.keyCode : $event.which
      if ((keyCode < 48 || keyCode > 57) && keyCode !== 46) {
        $event.preventDefault()
      }
    },
    initItems(filter, headers, filterMeta) {
      return headers
        .filter((column) => column.filter)
        .map((column) => {
          const item = {
            text: column.text,
            column: column.value,
            type: column.filter.type,
            value: null,
          }

          if (column.filter.items) {
            item.items = column.filter.items
          }

          if (column.filter.requestId) {
            item.requestId = column.filter.requestId
          }

          if (column.filter.request) {
            item.request = column.filter.request
          }

          if (column.filter.operator) {
            item.operator = column.filter.operator
          }

          if (this.isSimpleFilter(filterMeta)) {
            const filterItem = filter.find((andIndexItem) => andIndexItem[0].column === column.value)

            if (filterItem) {
              item.value = filterItem[0].value
            }
          }

          if (typeof column.filter.multiple !== 'undefined') {
            item.multiple = column.filter.multiple
          }

          if (typeof column.filter.requestOnFocus !== 'undefined') {
            item.requestOnFocus = column.filter.requestOnFocus
          }

          return item
        })
    },
    initFilter(filter, headers, filterMeta) {
      if (this.isSimpleFilter(filterMeta)) {
        return headers
          .filter((column) => column.filter)
          .map((column) => {
            let item = [
              {
                column: column.value,
                operator: OPERATOR.EQUAL,
                value: [],
              },
            ]

            const andItem = filter.find((andItem) => andItem[0].column === column.value)
            if (andItem) {
              item[0].value = andItem[0].value
              item[0].operator = andItem[0].operator
            }

            return item
          })
      } else {
        return []
      }
    },
    onChangeInput(andIndex, column, value, operator = OPERATOR.EQUAL) {
      upsertFilter(this.innerFilter, andIndex, 0, {
        column: column,
        operator,
        value: Array.isArray(value) ? value : [value],
      })

      this.onChange(clearFilter(this.innerFilter), { type: FILTER.SIMPLE_FILTER })
    },
    isSimpleFilter(meta) {
      return (
        meta && (meta.type === FILTER.SIMPLE_FILTER || meta.type === FILTER.QUICK_FILTER || meta.type === undefined)
      )
    },
  },
}
</script>
