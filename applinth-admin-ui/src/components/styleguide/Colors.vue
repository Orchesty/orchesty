<template>
  <div class="color-wrapper">
    <Color class="color-accent-blue" color-name="accent-blue" />
    <Color class="color-yellow" color-name="yellow" />
    <Color class="color-red" color-name="red" />
    <Color class="color-green" color-name="green" />
    <Color class="color-primary-blue" color-name="primary-blue" />
    <Color class="color-primary-text" color-name="primary-text" />
    <Color class="color-secondary-text" color-name="secondary-text" />
    <Color class="color-main-text" color-name="main-text" />
    <Color class="color-main-text-50" color-name="main-text-50" />
    <Color class="color-secondary-green" color-name="secondary-green" />
    <Color class="color-dark-blue" color-name="dark-blue" />
    <Color class="color-secondary-blue" color-name="secondary-blue" />
    <Color class="color-background" color-name="background" />
    <Color class="color-white" color-name="white" />
  </div>
</template>

<script lang="ts">
import { Component, Vue } from "vue-property-decorator";
import Color from "./Color.vue";

@Component({
  components: {
    Color,
  },
})
export default class AllColors extends Vue {}
</script>

<style lang="scss" scoped>
.color-wrapper {
  display: grid;
  grid-template-columns: repeat(5, 1fr);
}
</style>
