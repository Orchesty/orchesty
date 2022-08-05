<template>
  <AppLayout>
    <div class="table-medium">
      <h1>Billing</h1>
      <StatusCard
        class="customer-info-card my-5"
        :score="199"
        title="Actual billing"
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

@Component({
  components: {
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
