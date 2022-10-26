<template>
  <i class="card-cost-info mt-2">Cena platna k {{ toLocalDateTime(date) }}</i>
</template>

<script lang="ts">
import { Component, Prop, Vue } from "vue-property-decorator"
import { toLocalDateTime } from "@/filters/datetime"

//todo PIP-1448 add translation
@Component
export default class StatusCardCostInfo extends Vue {
  @Prop({ required: true, type: String })
  private date!: string

  readonly toLocalDateTime = toLocalDateTime
}
</script>

<style scoped lang="scss">
.card-cost-info {
  color: gray;
  font-size: 14px;
  display: inline-block;
}
</style>
