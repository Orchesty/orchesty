<template>
  <v-progress-linear color="primary" indeterminate rounded height="6" />
</template>

<script>
import { Component, Vue } from "vue-property-decorator";

@Component
export default class BaseProgressBarLinear extends Vue {}
</script>

<style scoped></style>
