<template>
  <v-card outlined :loading="loading" :height="height">
    <div v-if="expandableCardId || $slots.title" class="basic-card-title">
      <v-btn
        v-if="expandableCardId"
        class="basic-card-expansion-button"
        icon
        @click="handleExpand"
      >
        <v-icon v-if="expanded">mdi-chevron-up</v-icon>
        <v-icon v-else>mdi-chevron-down</v-icon>
      </v-btn>
      <slot name="title" />
    </div>
    <v-expand-transition>
      <div :style="height ? `height: ${height}` : ''" v-show="expanded">
        <slot />
      </div>
    </v-expand-transition>
  </v-card>
</template>

<script lang="ts">
import { Component, Prop, Vue } from "vue-property-decorator"

@Component
export default class Card extends Vue {
  @Prop({ required: false, type: Boolean, default: false })
  private loading!: boolean

  @Prop({ required: false, type: String, default: null })
  private height?: string

  @Prop({ required: false, type: String })
  private expandableCardId?: string

  private expanded = true

  constructor() {
    super()
  }

  private handleExpand(): void {
    if (!this.expandableCardId) return
    this.expanded = !this.expanded
  }
}
</script>
