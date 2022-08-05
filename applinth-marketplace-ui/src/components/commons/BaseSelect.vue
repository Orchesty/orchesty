<template>
  <v-select
    v-model="selectedItems"
    outlined
    :items="items"
    :label="label"
    :dense="dense"
  />
</template>

<script>
export default {
  name: 'BaseSelect',
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
    value: {
      type: Array,
      default: () => [],
    },
  },
  data() {
    return {
      selectedItems: [],
    }
  },
  watch: {
    selectedItems(value) {
      this.$emit('input', value)
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
