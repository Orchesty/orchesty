<template>
  <v-select
    v-model="selectedItems"
    :dense="dense"
    :outlined="outlined"
    :clearable="clearable"
    :readonly="readonly"
    :disabled="disabled"
    :label="label"
    :items="items"
    :item-value="itemValue"
    :item-text="itemKey"
  />
</template>

<script>
export default {
  name: "AppSelect",
  props: {
    items: {
      type: Array,
      required: true,
    },
    label: {
      type: String,
      required: true,
    },
    dense: {
      type: Boolean,
      default: true,
    },
    itemValue: {
      type: String,
      default: () => "value",
    },
    itemKey: {
      type: String,
      default: () => "key",
    },
    outlined: {
      type: Boolean,
      default: true,
    },
    clearable: {
      type: Boolean,
      default: true,
    },
    readonly: {
      type: Boolean,
      default: false,
    },
    disabled: {
      type: Boolean,
      default: false,
    },
    value: {
      type: String,
      default: () => "",
    },
  },
  data() {
    return {
      selectedItems: [],
    }
  },
  watch: {
    selectedItems(value) {
      this.$emit("input", value)
    },
    value: {
      immediate: true,
      handler(value) {
        this.selectedItems = value
      },
    },
  },
}
</script>

<style scoped></style>
