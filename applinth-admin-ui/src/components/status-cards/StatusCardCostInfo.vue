<template>
  <i v-if="dateString" class="card-cost-info">{{
    $t("valuesValidAsOfDate", { date: toLocalDateTime(dateString) })
  }}</i>
</template>

<script lang="ts">
import { Component, Vue } from "vue-property-decorator"
import { toLocalDateTime } from "@/filters/datetime"
import { LocalStorage } from "@/enums"

@Component
export default class StatusCardCostInfo extends Vue {
  dateString = localStorage.getItem(LocalStorage.priceCalculatedAt)

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
