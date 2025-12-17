<template>
  <v-progress-linear
    :indeterminate="indeterminate"
    :color="color"
    :height="height"
    :class="className"
  />
</template>

<script>
export default {
  name: "ProgressBarLinear",
  props: {
    color: {
      type: String,
      default: "primary",
    },
    indeterminate: {
      type: Boolean,
      default: true,
    },
    height: {
      type: Number,
      default: 2,
    },
    className: {
      type: String,
      default: "",
    },
  },
}
</script>

<style scoped></style>
