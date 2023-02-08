<template>
  <div>
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
      :multiple="multiple"
      :menu-props="{ bottom: true, offsetY: true }"
      :chips="chips"
    />
    <span v-if="renderErrorMessage" class="error--text">{{
      errorMessages[0]
    }}</span>
  </div>
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
      type: [String, Array],
      default: () => "",
    },
    multiple: {
      type: Boolean,
      default: false,
    },
    errorMessages: {
      type: Array,
      default: () => [],
    },
    chips: {
      type: Boolean,
      default: false,
    },
  },
  computed: {
    renderErrorMessage() {
      return this.errorMessages?.length > 0
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
