<template>
  <div>
    <h1 class="text-h1 mb-2">Heading 1</h1>
    <h2 class="text-h2 mb-2">Heading 2</h2>
    <h3 class="mb-2">Heading 3 with or without special class</h3>

    <p class="text-subtitle-1">Subtitle 1</p>

    <p class="text-subtitle-2">Subtitle 2</p>

    <p class="text-body-1">Body 1</p>

    <p class="text-body-2">Body 2</p>

    <p>
      Ullamcorper tempus, luctus eget, malesuada ut, velit. Morbi felis.
      Praesent in purus at ipsum cursus posuere. Morbi bibendum facilisis eros.
      Phasellus aliquam sapien in erat. Praesent venenatis diam dignissim dui.
      Praesent risus erat, iaculis ac, dapibus sed, imperdiet ac, erat. Nullam
      sed ipsum. Phasellus non dolor. Donec ut elit.
    </p>

    <p>
      <a href="google.com">Test link</a>
    </p>
  </div>
</template>

<script lang="ts">
import { Component, Vue } from "vue-property-decorator";

@Component
export default class Typography extends Vue {}
</script>

<style lang="scss" scoped></style>
