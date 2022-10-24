<template>
  <v-tooltip bottom :disabled="disableTooltip">
    <template v-slot:activator="{ on, attrs }">
      <v-icon
        v-on="on"
        v-bind="attrs"
        v-if="!disableIco"
        :color="color"
        class="badge"
        small
      >
        {{ `mdi-${icon}` }}
      </v-icon>
    </template>
    <span>{{ tooltip }}</span>
  </v-tooltip>
</template>

<script lang="ts">
import { Component, Prop, Vue } from "vue-property-decorator"

@Component
export default class Badge extends Vue {
  @Prop({ required: true, type: String })
  private tooltip!: string

  @Prop({ required: true, type: String })
  private icon!: string

  @Prop({ required: true, type: String })
  private color!: string

  @Prop({ required: false, type: Boolean, default: () => true })
  private disableTooltip!: boolean

  @Prop({ required: false, type: Boolean, default: () => true })
  private disableIco!: boolean
}
</script>

<style lang="scss" scoped>
.badge {
  position: relative;
  bottom: 2px;
}
</style>
