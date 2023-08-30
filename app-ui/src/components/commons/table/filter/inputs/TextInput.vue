<template>
  <validation-provider v-slot="{ errors, valid }" :rules="rules" :name="label.toLowerCase()">
    <v-text-field
      v-model="innerValue"
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
  name: 'TextInput',
  components: { ValidationProvider },
  props: {
    label: {
      type: String,
      required: true,
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
      this.onChange(this.innerValue)
    },
  },
}
</script>
