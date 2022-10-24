<template>
  <h1 class="headline font-weight-bold"><slot /></h1>
</template>

<script lang="ts">
import { Component, Vue } from "vue-property-decorator"

@Component
export default class Heading extends Vue {}
</script>

<style scoped></style>
