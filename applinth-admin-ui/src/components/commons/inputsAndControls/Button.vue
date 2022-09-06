<template>
  <v-btn
    :fab="fab"
    :icon="icon"
    :[type]="type"
    :height="icon ? '36' : height"
    :class="customClass"
    :color="color"
    :disabled="isSending ? true : disabled"
    :text="flat"
    :plain="plain"
    :width="width"
    :style="customStyle"
    :min-width="icon ? '36' : minWidth"
    :outlined="outlined"
    :loading="loading"
    :to="!!to ? to : null"
    @click="onClick"
    v-bind="attrs"
    v-on="on"
  >
    <template v-if="icon || fab">
      <slot name="icon"></slot>
    </template>

    <template v-else-if="isSending">
      {{ sendingTitle }}
    </template>

    <template v-else>
      <slot></slot>
    </template>
  </v-btn>
</template>

<script lang="ts">
import { Component, Prop, Vue } from "vue-property-decorator";

@Component
export default class Button extends Vue {
  @Prop({ required: false, type: String, default: "primary" })
  private color!: string;

  @Prop({ required: false, type: String, default: "" })
  private type!: string;

  @Prop({ required: false, type: String, default: "" })
  private sendingTitle!: string;

  @Prop({ required: false, type: Boolean, default: false })
  private loading!: boolean;

  @Prop({ required: false, type: Boolean, default: false })
  private disabled!: boolean;

  @Prop({ required: false, type: Boolean, default: false })
  private text!: boolean;

  @Prop({ required: false, type: Boolean, default: false })
  private outlined!: boolean;

  @Prop({ required: false, type: Boolean, default: false })
  private icon!: boolean;

  @Prop({ required: false, type: Boolean, default: false })
  private isSending!: boolean;

  @Prop({ required: false, type: Boolean, default: false })
  private plain!: boolean;

  @Prop({ required: false, type: Boolean, default: false })
  private flat!: boolean;

  @Prop({ required: false, type: Boolean, default: false })
  private fab!: boolean;

  // eslint-disable-next-line @typescript-eslint/no-empty-function
  @Prop({ required: false, type: Object, default: () => {} })
  private on!: boolean;

  // eslint-disable-next-line @typescript-eslint/no-empty-function
  @Prop({ required: false, type: Object, default: () => {} })
  private attrs!: boolean;

  // eslint-disable-next-line @typescript-eslint/no-empty-function
  @Prop({ required: false, type: Object, default: () => {} })
  private customStyle!: boolean;

  // eslint-disable-next-line @typescript-eslint/no-empty-function
  @Prop({ required: false, type: Function, default: () => {} })
  private onClick!: any;

  @Prop({ required: false, type: Number, default: 110 })
  private minWidth?: number;

  @Prop({ required: false, type: Number, default: 40 })
  private height!: number;

  @Prop({ required: false, type: String, default: "auto" })
  private width!: string;

  @Prop({ required: false, type: String, default: "" })
  private customClass!: string;

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
