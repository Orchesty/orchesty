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
import { Action, Getter } from "vuex-class";
import {
  StatusCardsActions,
  StatusCards,
  statusCardsNamespace,
  StatusCardsGetters,
} from "@/store/modules/status-cards";
import { StatusCardsState } from "@/store/modules/status-cards";

@Component({
  components: {
    StatusCard,
  },
})
export default class StatusCardList extends Vue {
  @Getter(`${statusCardsNamespace}/${StatusCardsGetters.GetState}`)
  numbers!: StatusCards;

  @Action(`${statusCardsNamespace}/${StatusCardsActions.Fetch}`)
  fetchStatusCards!: () => Promise<StatusCardsState>;

  created() {
    this.fetchStatusCards();
  }
}
</script>

<style lang="scss" scoped>
.wrapper {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 0 16px;
}
</style>
