<template>
  <div class="container">
    <div class="card rounded"></div>
    <p class="color-primary-blue text-body-2">{{ colorName }}</p>
  </div>
</template>

<script lang="ts">
import { Component, Prop, Vue } from "vue-property-decorator";

@Component
export default class Color extends Vue {
  @Prop({ required: true, type: String })
  private colorName!: string;
}
</script>

<style lang="scss" scoped>
.card {
  height: 60px;
  background-color: currentColor;
}
</style>
