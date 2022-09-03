<template>
  <div class="wrapper">
    <StatusCard :loading="true" :score="3" :title="$t('overviewPage.statusCards.applications')" />
    <StatusCard :loading="false" :score="142" :title="$t('overviewPage.statusCards.installations')" />
    <StatusCard :loading="true" :score="150" :title="$t('overviewPage.statusCards.users')" />
    <StatusCard :loading="true" :score="28258" :title="$t('overviewPage.statusCards.billing')" />
  </div>
</template>

<script lang="ts">
import { Component, Vue } from "vue-property-decorator";
import StatusCard from "../commons/StatusCard.vue";

@Component({
  components: {
    StatusCard,
  },
})
export default class StatusCardList extends Vue {}
</script>

<style lang="scss" scoped>
.wrapper {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 0 16px;
}
</style>
