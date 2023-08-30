<template>
  <v-text-field
    v-model="input"
    :dense="dense"
    :label="label"
    :type="inputType"
    :outlined="outlined"
    :error-messages="errorMessages[0]"
    :prepend-inner-icon="prependIcon"
    :readonly="readonly"
    :disabled="disabled"
    :hide-details="hideDetails"
    :clearable="clearable"
    @keypress="onKeyup(input)"
  />
</template>

<script>
export default {
  name: 'AppInput',
  props: {
    errorMessages: {
      type: Array,
      required: false,
      default: () => [],
    },
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
    numbersOnly: {
      type: Boolean,
      default: false,
    },
    readonly: {
      type: Boolean,
      required: false,
      default: false,
    },
    value: {
      type: [String, Number],
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
      input: '',
    }
  },
  methods: {
    onKeyup(input) {
      if (this.numbersOnly) {
        if (typeof input !== 'number') {
          this.input = input.replace(/\D/g, '')
        }
      }
    },
  },
  watch: {
    input(value) {
      this.$emit('input', value)
    },
    value: {
      immediate: true,
      handler(value) {
        this.input = value
      },
    },
  },
}
</script>

<style>
.v-input input {
  font-size: 0.95em;
}
.v-input .v-label {
  font-size: 0.95em;
}
</style>
