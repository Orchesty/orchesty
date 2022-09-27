<template>
  <v-text-field
    v-model="innerValue"
    :dense="dense"
    :label="label"
    :type="inputType"
    :outlined="outlined"
    :readonly="readonly"
    :disabled="disabled"
    :hide-details="hideDetails"
    :clearable="clearable"
    :prepend-icon="prependIcon"
    :error-messages="errorMessages"
  />
</template>

<script>
export default {
  name: 'BaseInput',
  props: {
    inputType: {
      type: String,
      required: false,
      default: () => 'text',
    },
    errorMessages: {
      type: Array,
      required: true,
    },
    dense: {
      type: Boolean,
      default: true,
    },
    hideDetails: {
      type: Boolean,
      default: false,
    },
    disabled: {
      type: Boolean,
      default: false,
    },
    outlined: {
      type: Boolean,
      default: true,
    },
    prependIcon: {
      type: String,
      default: undefined,
    },
    clearable: {
      type: Boolean,
      default: false,
    },
    readonly: {
      type: Boolean,
      default: false,
    },
    numbersOnly: {
      type: Boolean,
      default: false,
    },
    value: {
      type: [String, Number, Boolean],
      required: false,
      default: () => '',
    },
    label: {
      type: String,
      required: true,
    },
  },
  data() {
    return {
      innerValue: '',
    }
  },
  watch: {
    innerValue(value) {
      this.$emit('input', value)
    },
    value: {
      immediate: true,
      handler(value) {
        this.innerValue = value
      },
    },
  },
}
</script>
