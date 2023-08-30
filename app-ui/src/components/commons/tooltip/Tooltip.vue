<template>
  <v-tooltip v-bind="{ [orientation]: true }">
    <template #activator="{ on, attrs }">
      <slot name="activator" v-bind="{ on, attrs }"> </slot>
    </template>
    <slot name="tooltip"> </slot>
  </v-tooltip>
</template>

<script>
export default {
  name: 'Tooltip',
  props: {
    orientation: {
      type: String,
      default: 'bottom',
    },
  },
}
</script>

<style scoped></style>
