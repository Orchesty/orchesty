<template>
  <v-text-field
    v-model="input"
    :dense="dense"
    :label="label"
    :type="inputType"
    :outlined="outlined"
    :error-messages="errorMessages[0]"
    :prepend-icon="prependIcon"
    :readonly="readonly"
    :disabled="disabled"
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
    readonly: {
      type: Boolean,
      required: false,
      default: false,
    },
    value: {
      type: String,
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
