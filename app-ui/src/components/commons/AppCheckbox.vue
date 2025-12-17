<template>
  <v-checkbox
    v-model="innerValue"
    :label="label"
    :readonly="readonly"
    :disabled="disabled"
    dense
  />
</template>

<script>
export default {
  name: "AppCheckbox",
  props: {
    label: {
      type: String,
      required: true,
    },
    value: {
      type: Boolean,
      default: false,
    },
    readonly: {
      type: Boolean,
      default: false,
    },
    disabled: {
      type: Boolean,
      default: false,
    },
  },
  data() {
    return {
      innerValue: false,
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

<style scoped></style>
