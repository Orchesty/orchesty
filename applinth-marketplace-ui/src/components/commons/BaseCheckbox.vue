<template>
  <v-checkbox v-model="innerValue" :label="label" dense />
</template>

<script>
export default {
  name: 'BaseCheckbox',
  props: {
    label: {
      type: String,
      required: true,
    },
    value: {
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

<style scoped></style>
