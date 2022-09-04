<template>
  <AppLayout>
    <div class="table-medium">
      <Heading>{{ $t("customerBillingPage.header") }}</Heading>
      <!--      TODO HARDCOCED-->
      <StatusCard
        class="customer-info-card my-5"
        :score="199"
        :title="$t('customerBillingPage.statusCard.actualBilling')"
      />
      <CustomerBillingTable />
    </div>
  </AppLayout>
</template>

<script lang="ts">
import { Component, Vue } from "vue-property-decorator";
import AppLayout from "../components/commons/layouts/AppLayout.vue";
import StatusCard from "@/components/commons/StatusCard.vue";
import CustomerBillingTable from "@/components/app/CustomerBillingTable.vue";
import Heading from "@/components/Heading.vue";

@Component({
  components: {
    Heading,
    CustomerBillingTable,
    StatusCard,
    AppLayout,
  },
})
export default class UserDetailPage extends Vue {}
</script>

<style lang="scss" scoped>
.customer-info-card {
  max-width: 200px;
}
</style>
