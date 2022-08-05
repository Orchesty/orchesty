<template>
  <v-tooltip v-bind="{ [orientation]: true }">
    <template #activator="{ on, attrs }">
      <slot name="activator" :attrs="attrs" :on="on"> </slot>
    </template>
    <span class="font-weight-medium">
      <slot name="tooltip"> </slot>
    </span>
  </v-tooltip>
</template>

<script>
export default {
  name: 'BaseTooltip',
  props: {
    orientation: {
      type: String,
      default: 'bottom',
    },
  },
}
</script>

<style scoped></style>
