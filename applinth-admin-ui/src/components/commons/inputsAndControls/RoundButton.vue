<template>
  <v-btn
    @click="$emit('click')"
    icon
    :outlined="outlined"
    :large="isMobile() || large"
    :disabled="disabled"
    :color="color"
    :to="to"
  >
    <v-icon v-if="icon" :large="isMobile()">{{ `mdi-${icon}` }}</v-icon>
    <slot />
  </v-btn>
</template>

<script lang="ts">
import { Component, Prop, Vue } from "vue-property-decorator"
import MobileMixin from "../../../mixin/MobileMixin"

@Component({ mixins: [MobileMixin] })
export default class RoundButton extends Vue {
  @Prop({
    required: false,
    type: String,
    default: "",
    validator: (icon: string) =>
      [
        "close",
        "pencil",
        "plus",
        "delete",
        "pin",
        "pin-outline",
        "chevron-left",
        "chevron-right",
        "arrow-right-circle-outline",
        "check-bold",
      ].includes(icon),
  })
  private icon!: string

  @Prop({ type: Boolean, default: false })
  private outlined!: boolean

  @Prop({ type: Boolean, default: false })
  private disabled!: boolean

  @Prop({ type: Boolean, default: false })
  private large!: boolean

  @Prop({ type: String, default: "" })
  private color!: string

  @Prop({ type: [String, Object], default: null })
  private to!: object | string
}
</script>

<style lang="scss" scoped></style>
