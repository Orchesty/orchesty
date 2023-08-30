<template>
  <validation-provider v-slot="{ errors, valid }" :rules="rules" :name="label.toLowerCase()">
    <v-select
      v-model="innerValue"
      :items="items"
      menu-props="offsetY"
      :label="label"
      :clearable="clearable"
      :error-messages="errors[0]"
      outlined
      dense
      :disabled="disabled"
      :hide-details="valid"
    />
  </validation-provider>
</template>

<script>
import { ValidationProvider } from 'vee-validate'

export default {
  name: 'SelectBoxInput',
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
    items: {
      type: Array,
      required: false,
      default: () => [],
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
    disabled: {
      type: Boolean,
      default: false,
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
