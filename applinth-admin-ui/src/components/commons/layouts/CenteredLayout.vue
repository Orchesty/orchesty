<template>
  <div class="centered-layout">
    <main class="main">
      <slot />
    </main>
  </div>
</template>

<script lang="ts">
import { Component, Vue } from "vue-property-decorator";

@Component
export default class CenteredLayout extends Vue {}
</script>

<style lang="scss" scoped>
.centered-layout {
  display: flex;
  height: 100%;

  .main {
    margin: auto;
  }
}
</style>
