<template>
  <AppLayout :detail-page-title="breadcrumbTitle">
    <heading>{{ breadcrumbTitle }}</heading>
    <div class="wrapper">
      <StatusCard
        class="customer-info-card my-5"
        :score="toCZK(totalCost)"
        :title="$t('customerDetailPage.currentCost')"
        :loading="isLoading"
      />
      <StatusCard
        class="customer-info-card my-5"
        :score="toCZK(estimatedCost)"
        :title="$t('customerDetailPage.estimatedCosts')"
        :loading="isLoading"
      />
    </div>

    <SubHeading class="mb-2">{{
      $t("customerDetailPage.activeApplications")
    }}</SubHeading>
    <CustomerAppsTable class="apps-table mb-5" :customer-id="customerId" />
    <SubHeading class="mb-2">{{
      $t("customerDetailPage.monthlyPayments")
    }}</SubHeading>
    <CustomerBillingTable :customer-id="customerId" />
  </AppLayout>
</template>

<script lang="ts">
import { Component, Vue } from "vue-property-decorator"
import AppLayout from "../components/commons/layouts/AppLayout.vue"
import StatusCard from "@/components/commons/layouts/StatusCard.vue"
import CustomerAppsTable from "@/components/app/CustomerAppsTable.vue"
import CustomerBillingTable from "@/components/app/CustomerBillingTable.vue"
import Heading from "@/components/commons/typography/Heading.vue"
import SubHeading from "@/components/commons/typography/SubHeading.vue"
import { callApi } from "@/utils"
import { api } from "@/api"
import {
  UsageStatsUsersRequest,
  UsageStatsUsersRowsInner,
} from "@/api/generated"
import { toCZK } from "@/filters/money"

@Component({
  components: {
    Heading,
    SubHeading,
    CustomerBillingTable,
    CustomerAppsTable,
    AppLayout,
    StatusCard,
  },
})
export default class CustomerDetailPage extends Vue {
  customerId!: string
  customerDetail!: UsageStatsUsersRowsInner
  isLoading = false
  totalCost: number | undefined = 0
  estimatedCost: number | undefined = 0
  breadcrumbTitle: string | undefined = ""

  async created() {
    this.isLoading = true
    this.customerId = this.$route.params.id

    const customer = await callApi<UsageStatsUsersRequest>(api.customers.list, {
      endUserId: this.customerId,
      granularity: "monthly",
    })

    if (customer?.length > 0) {
      this.customerDetail = customer[0]
      this.breadcrumbTitle = this.customerDetail?.endUserDisplayId
      this.totalCost = this.customerDetail.totalCost
      this.estimatedCost = this.customerDetail.estimatedTotalCost
    } else {
      this.breadcrumbTitle = this.customerId
    }

    this.isLoading = false
  }

  readonly toCZK = toCZK
}
</script>

<style lang="scss" scoped>
.customer-info-card {
  max-width: 200px;
}

.apps-table {
  max-width: clamp(50ch, 50vw, 700px);
}
.wrapper {
  display: inline-grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 0 16px;
}
</style>
