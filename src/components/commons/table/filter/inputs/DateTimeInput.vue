<template>
  <validation-provider v-slot="{ errors, valid }" :rules="rules" :name="label.toLowerCase()">
    <v-datetime-picker
      v-model="innerValue"
      :label="label"
      :text-field-props="{
        outlined: true,
        dense: true,
        'error-messages': errors[0],
        'hide-details': valid,
      }"
      :date-picker-props="{ locale: $i18n.locale, 'first-day-of-week': 1 }"
      :time-picker-props="{ format: '24hr' }"
      :date-format="getComponentFormat()"
      :time-format="getTimeFormat()"
    />
  </validation-provider>
</template>

<script>
import { ValidationProvider } from 'vee-validate'
import moment from 'moment'

export default {
  name: 'DateTimeInput',
  components: { ValidationProvider },
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
      type: String,
      required: false,
      default: '',
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
      innerValue: this.formatDate(this.value),
    }
  },
  watch: {
    value(newVal) {
      this.innerValue = this.formatDate(newVal)
    },
    innerValue() {
      this.onChange(moment(this.innerValue, `${this.getMomentDateFormat()} ${this.getTimeFormat()}`).toISOString())
    },
  },
  methods: {
    getMomentDateFormat() {
      return moment.localeData(this.$i18n.locale).longDateFormat('L')
    },
    getComponentFormat() {
      return this.getMomentDateFormat().replace(/D/g, 'd').replace(/Y/g, 'y')
    },
    getTimeFormat() {
      return moment.localeData(this.$i18n.locale).longDateFormat('LT')
    },
    formatDate(datetime) {
      if (!datetime) {
        return
      }

      return moment(datetime).format(`${this.getMomentDateFormat()} ${this.getTimeFormat()}`)
    },
  },
}
</script>
