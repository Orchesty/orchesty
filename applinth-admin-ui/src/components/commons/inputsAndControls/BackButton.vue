<template>
  <Button color="secondary" @click="handleClick">
    <v-icon left>mdi-chevron-left</v-icon> {{ $t("button.back") }}
  </Button>
</template>

<script lang="ts">
import { Component, Vue } from "vue-property-decorator";
import { RawLocation } from "vue-router";
import { router, routerHistory } from "../../../utils";

import Button from "./Button.vue";

@Component({
  components: {
    Button,
  },
})
export default class BackButton extends Vue {
  private readonly previousRoute: RawLocation;

  constructor() {
    super();
    this.previousRoute = routerHistory[routerHistory.length - 1];
  }

  private handleClick(): void {
    router.push(this.previousRoute);
  }
}
</script>

<style lang="scss" scoped></style>
