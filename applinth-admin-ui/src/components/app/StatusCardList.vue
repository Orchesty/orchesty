<template>
  <div class="wrapper">
    <StatusCard :score="3" title="Applications" />
    <StatusCard :score="142" title="Installations" />
    <StatusCard :score="150" title="Users" />
    <StatusCard :score="28258" title="Billing" />
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
