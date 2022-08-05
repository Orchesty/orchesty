<template>
  <AppLayout>
    <div class="table-medium">
      <h1>John Doe</h1>
      <StatusCard
        class="cusotmer-info-card my-5"
        :score="199"
        title="Actual billing"
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

@Component({
  components: {
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
