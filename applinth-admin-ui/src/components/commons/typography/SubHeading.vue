<template>
  <h2 class="title font-weight-bold"><slot /></h2>
</template>

<script>
import { Component, Vue } from "vue-property-decorator";

@Component
export default class SubHeading extends Vue {}
</script>

<style scoped></style>
