<template>
  <validation-provider v-slot="{ errors, valid }" :rules="rules" :name="label.toLowerCase()">
    <v-menu v-model="isOpen" :close-on-content-click="false" offset-y transition="scale-transition" min-width="290px">
      <template #activator="{ on }">
        <v-text-field
          :label="label"
          readonly
          :value="innerValue | toLocalDate"
          :error-messages="errors[0]"
          :hide-details="valid"
          dense
          outlined
          v-on="on"
        />
      </template>
      <v-date-picker v-model="innerValue" :locale="$i18n.locale" :first-day-of-week="1" />
    </v-menu>
  </validation-provider>
</template>

<script>
import { ValidationProvider } from 'vee-validate'
import { toLocalDate } from '../../../../../filters'

export default {
  name: 'DatePicker',
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
      innerValue: this.value || null,
      isOpen: false,
    }
  },
  watch: {
    innerValue() {
      this.onChange(this.innerValue)
    },
    value(val) {
      this.innerValue = val
    },
  },
  filters: {
    toLocalDate,
  },
}
</script>
