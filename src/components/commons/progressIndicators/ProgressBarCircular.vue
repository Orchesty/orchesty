<template>
  <v-progress-circular
    indeterminate
    color="primary"
    :size="size"
    :width="width"
  />
</template>

<script>
export default {
  name: "ProgressBarCircular",
  props: {
    size: {
      type: Number,
      default: 32,
    },
    width: {
      type: Number,
      default: 4,
    },
  },
}
</script>
