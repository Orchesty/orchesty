<template>
  <v-icon :color="color" :dense="dense">
    <slot></slot>
  </v-icon>
</template>

<script>
export default {
  name: 'AppIcon',
  props: {
    color: {
      type: String,
      default: 'primary',
    },
    dense: {
      type: Boolean,
      default: false,
    },
  },
}
</script>

<style scoped></style>
