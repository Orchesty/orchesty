<template>
  <svg
    class="svg-icon"
    :class="{ fill, stroke, small, large }"
    aria-hidden="true"
  >
    <use :xlink:href="name" />
  </svg>
</template>

<script lang="ts">
import { Component, Prop, Vue } from "vue-property-decorator";

const requireAll = (requireContext: any) =>
  requireContext.keys().map(requireContext);
const req = require.context("../../assets/img/icons", false, /\.svg$/);
requireAll(req);

@Component
export default class SvgIcon extends Vue {
  @Prop({ type: String, required: true })
  private iconName!: string;

  @Prop({ type: Boolean, default: false })
  private fill!: boolean;

  @Prop({ type: Boolean, default: false })
  private stroke!: boolean;

  @Prop({ type: Boolean, default: false })
  private small!: boolean;

  @Prop({ type: Boolean, default: false })
  private large!: boolean;

  get name(): string {
    let icon = this.iconName;
    return icon ? `#icon-${icon}` : "";
  }
}
</script>

<style lang="scss" scoped>
.svg-icon {
  font-size: 1.5rem;
  width: 1em;
  height: 1em;
  overflow: hidden;
}

.small {
  font-size: 1rem;
}

.large {
  font-size: 2.25rem;
}

.fill {
  fill: currentColor;
  stroke: none;
}

.stroke {
  stroke: currentColor;
  fill: none;
}
</style>
