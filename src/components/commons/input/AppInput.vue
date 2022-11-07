<template>
  <v-text-field
    v-model="innerValue"
    :autofocus="autofocus"
    :dense="dense"
    :label="label"
    :type="inputType"
    :outlined="outlined"
    :readonly="readonly"
    :disabled="disabled"
    :hide-details="hideDetails"
    :clearable="clearable"
    :prepend-icon="prependIcon"
    :error-messages="errorMessages[0]"
    :append-icon="appendIcon"
    @click:append="$emit('appendIconClicked')"
  />
</template>

<script>
export default {
  name: "AppInput",
  props: {
    errorMessages: {
      type: Array,
      default: () => [],
    },
    inputType: {
      type: String,
      default: "text",
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
    readonly: {
      type: Boolean,
      default: false,
    },
    value: {
      type: [String, Number, Boolean],
      default: "",
    },
    label: {
      type: String,
      required: true,
    },
    appendIcon: {
      type: String,
      default: "",
    },
  },
  data() {
    return {
      innerValue: "",
    }
  },
  watch: {
    innerValue(value) {
      this.$emit("input", value)
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

<style>
.v-input input {
  font-size: 0.875rem;
}

.v-input .v-label {
  font-size: 0.875rem;
}
</style>
