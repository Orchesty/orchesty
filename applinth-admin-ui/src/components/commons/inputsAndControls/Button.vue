<template>
  <v-btn
    :type="type"
    :block="block"
    :loading="loading"
    depressed
    @click="$emit('click', $event)"
    :color="color"
    small
    :large="large"
    class="button font-weight-bold"
    :class="{ 'no-text-transform': noTextTransform }"
    :text="text"
    :disabled="disabled"
    :min-width="minWidth"
    :outlined="outlined"
    :icon="icon"
    :to="to"
  >
    <slot></slot>
  </v-btn>
</template>

<script lang="ts">
import { Component, Prop, Vue } from "vue-property-decorator";

@Component
export default class Button extends Vue {
  @Prop({ required: false, type: String, default: "" })
  private color!: string;

  @Prop({ required: false, type: String, default: "button" })
  private type!: "button" | "submit";

  @Prop({ required: false, type: Boolean, default: false })
  private loading!: boolean;

  @Prop({ required: false, type: Boolean, default: false })
  private block!: boolean;

  @Prop({ required: false, type: Boolean, default: false })
  private small!: boolean;

  @Prop({ required: false, type: Boolean, default: false })
  private large!: boolean;

  @Prop({ required: false, type: Boolean, default: false })
  private noTextTransform!: boolean;

  @Prop({ required: false, type: Boolean, default: false })
  private disabled!: boolean;

  @Prop({ required: false, type: Boolean, default: false })
  private text!: boolean;

  @Prop({ required: false, type: Boolean, default: false })
  private outlined!: boolean;

  @Prop({ required: false, type: Boolean, default: false })
  private icon!: boolean;

  @Prop({ required: false, type: Number, default: 150 })
  private minWidth?: number;

  @Prop({ required: false, type: [String, Object] })
  private to?: string | object;
}
</script>

<style lang="scss" scoped>
.button {
  font-family: $font-family;
  letter-spacing: 0;
}

.no-text-transform ::v-deep .v-btn__content {
  text-transform: none;
}
</style>
