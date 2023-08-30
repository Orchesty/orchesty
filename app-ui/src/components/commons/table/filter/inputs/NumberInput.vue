<template>
  <validation-provider v-slot="{ errors, valid }" :rules="rules" :name="label.toLowerCase()">
    <v-text-field
      v-model="innerValue"
      type="number"
      :label="label"
      :clearable="clearable"
      :error-messages="errors[0]"
      outlined
      dense
      :hide-details="valid"
    />
  </validation-provider>
</template>

<script>
import { ValidationProvider } from 'vee-validate'

export default {
  name: 'NumberInput',
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
      type: [String, Number],
      required: false,
      default: null,
    },
    onChange: {
      type: Function,
      required: true,
    },
    clearable: {
      type: Boolean,
      default: false,
    },
    rules: {
      type: [Object, String],
      default: '',
    },
  },
  data() {
    return {
      innerValue: this.value,
    }
  },
  watch: {
    value(newVal) {
      this.innerValue = newVal
    },
    innerValue() {
      this.onChange(parseInt(this.innerValue))
    },
  },
}
</script>
