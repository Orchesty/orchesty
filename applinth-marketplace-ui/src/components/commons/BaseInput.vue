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
    dense: {
      type: Boolean,
      required: false,
      default: true,
    },
    hideDetails: {
      type: Boolean,
      required: false,
      default: false,
    },
    disabled: {
      type: Boolean,
      required: false,
      default: false,
    },
    outlined: {
      type: Boolean,
      required: false,
      default: true,
    },
    prependIcon: {
      type: String,
      required: false,
      default: undefined,
    },
    clearable: {
      type: Boolean,
      required: false,
      default: false,
    },
    readonly: {
      type: Boolean,
      required: false,
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
