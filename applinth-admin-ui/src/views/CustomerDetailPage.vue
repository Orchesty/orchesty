<template>
  <AppLayout :detail-page-title="breadcrumbTitle">
    <div class="table-medium">
      <template v-if="!isLoading && customerDetail">
        <heading>{{ customerDetail.endUserDisplayId }}</heading>
        <StatusCard
          class="cusotmer-info-card my-5"
          :score="formatTotalCost"
          :title="$t('customerDetailPage.statusCard.estimatedCost')"
        />
      </template>
      <CustomerAppsTable class="mb-5" :customer-id="customerId" />
      <CustomerBillingTable :customer-id="customerId" />
    </div>
  </AppLayout>
</template>

<script lang="ts">
import { Component, Vue } from "vue-property-decorator";
import AppLayout from "../components/commons/layouts/AppLayout.vue";
import StatusCard from "@/components/commons/layouts/StatusCard.vue";
import CustomerAppsTable from "@/components/app/CustomerAppsTable.vue";
import CustomerBillingTable from "@/components/app/CustomerBillingTable.vue";
import Heading from "@/components/commons/typography/Heading.vue";
import { callApi } from "@/utils";
import { api } from "@/api";
import {
  UsageStatsUsersRequest,
  UsageStatsUsersRowsInner,
} from "@/api/generated";
import { toCZK } from "@/filters/money";

@Component({
  components: {
    Heading,
    CustomerBillingTable,
    CustomerAppsTable,
    AppLayout,
    StatusCard,
  },
})
export default class CustomerDetailPage extends Vue {
  customerId!: string;
  customerDetail!: UsageStatsUsersRowsInner;
  isLoading = false;
  totalCost!: number;
  breadcrumbTitle: string | undefined = "";

  get formatTotalCost(): string {
    if (typeof this.customerDetail.totalCost === "number")
      return toCZK(this.customerDetail.totalCost);
    return "";
  }

  async created() {
    this.isLoading = true;
    this.customerId = this.$route.params.id;

    const customer = await callApi<UsageStatsUsersRequest>(api.customers.list, {
      timeRangeStart: new Date(0).toISOString(),
      timeRangeEnd: new Date().toISOString(),
      endUserId: this.customerId,
    });

    if (customer.length > 0) {
      this.customerDetail = customer[0];
      this.breadcrumbTitle = this.customerDetail?.endUserDisplayId;
    }

    this.isLoading = false;
  }
}
</script>

<style lang="scss" scoped>
.cusotmer-info-card {
  max-width: 200px;
}
</style>
