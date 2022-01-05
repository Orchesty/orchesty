<template>
  <v-container fluid class="pa-0">
    <v-row>
      <v-col cols="6" class="py-0">
        <date-time-input :label="from" :value="innerValueFrom" :on-change="onChangeFrom" />
      </v-col>
      <v-col cols="6" class="py-0">
        <date-time-input :label="to" :value="innerValueTo" :on-change="onChangeTo" />
      </v-col>
    </v-row>
  </v-container>
</template>

<script>
import DateTimeInput from './DateTimeInput'

export default {
  name: 'DateTimeBetweenInput',
  components: {
    DateTimeInput,
  },
  props: {
    label: {
      type: String,
      required: true,
    },
    column: {
      type: String,
      required: false,
      default: '',
    },
    value: {
      type: Array,
      required: false,
      default: () => [],
    },
    onChange: {
      type: Function,
      required: true,
    },
    rules: {
      type: [Object, String],
      default: '',
    },
  },
  data() {
    return {
      innerValueFrom: this.value[0] || null,
      innerValueTo: this.value[1] || null,
    }
  },
  computed: {
    from() {
      return `${this.label} - ${this.$i18n.t('dataGrid.inputs.between.from')}`
    },
    to() {
      return `${this.label} - ${this.$i18n.t('dataGrid.inputs.between.to')}`
    },
  },
  watch: {
    value(newVal) {
      this.innerValueFrom = newVal[0] || null
      this.innerValueTo = newVal[1] || null
    },
    innerValueFrom() {
      this.onChange([this.innerValueFrom, this.innerValueTo])
    },
    innerValueTo() {
      this.onChange([this.innerValueFrom, this.innerValueTo])
    },
  },
  methods: {
    onChangeFrom(val) {
      this.innerValueFrom = val
    },
    onChangeTo(val) {
      this.innerValueTo = val
    },
  },
}
</script>
