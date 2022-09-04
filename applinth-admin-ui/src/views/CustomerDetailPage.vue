<template>
  <AppLayout>
    <div class="table-medium">
      <!--      TODO HARDCOCED-->

      <heading>John Doe</heading>
      <StatusCard
        class="cusotmer-info-card my-5"
        :score="199"
        :title="$t('customerDetailPage.statusCard.actualBilling')"
      />
      <CustomerAppsTable class="mb-5" />
      <CustomerBillingTable />
    </div>
  </AppLayout>
</template>

<script lang="ts">
import { Component, Vue } from "vue-property-decorator";
import AppLayout from "../components/commons/layouts/AppLayout.vue";
import StatusCard from "@/components/commons/StatusCard.vue";
import CustomerAppsTable from "@/components/app/CustomerAppsTable.vue";
import CustomerBillingTable from "@/components/app/CustomerBillingTable.vue";
import Heading from "@/components/Heading.vue";

@Component({
  components: {
    Heading,
    CustomerBillingTable,
    CustomerAppsTable,
    AppLayout,
    StatusCard,
  },
})
export default class UserDetailPage extends Vue {}
</script>

<style lang="scss" scoped>
.cusotmer-info-card {
  max-width: 200px;
}
</style>
