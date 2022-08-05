<template>
  <img class="logo" :src="logo" alt="Logo" />
</template>

<script lang="ts">
import { Component, Vue } from "vue-property-decorator";
import logo from "@/assets/img/logo.svg";

@Component
export default class Logo extends Vue {
  logo = logo;
}
</script>

<style lang="scss" scoped>
.logo {
  max-width: 100%;
  max-height: 100%;
  width: auto;
  height: auto;
  align-self: baseline;
}
</style>
