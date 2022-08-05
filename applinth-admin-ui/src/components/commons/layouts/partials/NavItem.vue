<template>
  <v-list-item
    @click="handleClick"
    :to="to"
    :style="
      (primary ? 'color: white !important;' : '') +
      (primary
        ? `background: ${$vuetify.theme.currentTheme.primary} !important;`
        : '') +
      (main
        ? `background: ${$vuetify.theme.currentTheme['light-grey']} !important;`
        : '')
    "
    :exact="exact"
  >
    <v-tooltip :disabled="!navMiniVariant" right>
      <template v-slot:activator="{ on, attrs }">
        <v-list-item-icon v-bind="attrs" v-on="on">
          <v-icon :color="primary ? 'white' : ''">{{ icon }}</v-icon>
        </v-list-item-icon>
      </template>
      <span>{{ label }}</span>
    </v-tooltip>
    <v-list-item-title>
      {{ label }}
    </v-list-item-title>
  </v-list-item>
</template>

<script lang="ts">
import { Component, Prop, Vue } from "vue-property-decorator";
import { Location } from "vue-router";

@Component
export default class NavItem extends Vue {
  @Prop({ required: true, type: Boolean })
  private navMiniVariant!: boolean;

  @Prop({ required: true, type: String })
  private label!: string;

  @Prop({ required: true, type: String })
  private icon!: string;

  @Prop({ required: false, type: Object })
  private to!: Location;

  @Prop({ required: false, type: Boolean, default: false })
  private primary!: boolean;

  @Prop({ required: false, type: Boolean, default: false })
  private exact!: boolean;

  @Prop({ required: false, type: Boolean, default: false })
  private main!: boolean;

  private handleClick(): void {
    this.$emit("click");
  }
}
</script>

<style lang="scss" scoped></style>
