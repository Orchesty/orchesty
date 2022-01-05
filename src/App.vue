<template>
  <router-view :key="$route.path" />
</template>

<script>
export default {
  name: 'App',
}
</script>

<style lang="scss">
@import 'assets/scss/main';
</style>
