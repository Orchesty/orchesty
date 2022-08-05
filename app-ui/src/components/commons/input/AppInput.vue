<template>
  <v-text-field
    v-model="input"
    :autofocus="autofocus"
    :dense="dense"
    :label="label"
    :type="inputType"
    :outlined="outlined"
    :error-messages="errorMessages[0]"
    :readonly="readonly"
    :disabled="disabled"
    :hide-details="hideDetails"
    :clearable="clearable"
    @keypress="onKeyup"
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
      default: true,
    },
    autofocus: {
      type: Boolean,
      default: false,
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
    numbersOnly: {
      type: Boolean,
      default: false,
    },
    readonly: {
      type: Boolean,
      default: false,
    },
    value: {
      type: [String, Number],
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
    onKeyup(event) {
      if (this.numbersOnly) {
        event = event ? event : window.event
        let expect = event.target.value.toString() + event.key.toString()

        if (!/^[-+]?[0-9]*\.?[0-9]*$/.test(expect)) {
          event.preventDefault()
        } else {
          return true
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
  font-size: 0.875rem;
}
.v-input .v-label {
  font-size: 0.875rem;
}
</style>
