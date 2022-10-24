<template>
  <div class="wrapper">
    <slot></slot>
  </div>
</template>

<script>
export default {
  name: "ActionsWrapper",
}
</script>

<style scoped>
.wrapper {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}
</style>
