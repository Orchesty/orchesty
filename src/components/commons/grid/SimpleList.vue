<template>
  <v-data-table
    :headers="headers"
    :items="items"
    :item-key="itemKey"
    :loading="loading"
    hide-default-footer
    hide-default-header
  >
    <template v-for="header in headers" #[`item.${header.value}`]="{ item }">
      <slot :name="header.value" :item="item">{{ item[header.value] }}</slot>
    </template>
  </v-data-table>
</template>

<script>
export default {
  name: 'SimpleList',
  props: {
    headers: {
      type: Array,
      required: true,
    },
    items: {
      type: Array,
      required: true,
    },
    itemKey: {
      type: String,
      required: true,
    },
    loading: {
      type: Boolean,
      required: false,
      default: false,
    },
  },
}
</script>

<style scoped></style>
