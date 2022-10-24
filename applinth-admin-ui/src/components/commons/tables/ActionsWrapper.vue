<template>
  <div class="actions">
    <slot />
  </div>
</template>

<script lang="ts">
import { Component, Vue } from "vue-property-decorator"

@Component
export default class ActionsWrapper extends Vue {}
</script>

<style lang="scss" scoped>
.actions {
  display: flex;

  & > *:not(:last-child) {
    margin-right: 0.2rem;
  }
}
</style>
