<template>
  <AppLayout>
    <div class="table-medium">
      <Heading>{{ $t("customersPage.header") }}</Heading>
      <div class="wrapper my-5">
        <TextField
          v-model="textSearch"
          hide-details
          :name="$t('formLabels.search')"
          :label="$t('formLabels.search')"
        />
        <SelectBox
          v-model="appSearch"
          hide-details
          :label="$t('formLabels.filterByApplication')"
          :items="applications"
          :name="$t('formLabels.filterByApplication')"
          item-text="appName"
          item-value="appName"
        />
        <Button class="ma-auto" icon :on-click="resetFilters">
          <template #icon>
            <v-icon>close</v-icon>
          </template>
        </Button>
      </div>
      <SimpleTable class="table-medium" :headers="headers" :items="customers" />
    </div>
  </AppLayout>
</template>

<script lang="ts">
import { Component, Vue, Watch } from "vue-property-decorator";
import SimpleTable from "@/components/commons/tables/SimpleTable.vue";
import AppLayout from "../components/commons/layouts/AppLayout.vue";
import Button from "../components/commons/inputsAndControls/Button.vue";
import SelectBox from "@/components/commons/inputsAndControls/SelectBox.vue";
import TextField from "@/components/commons/inputsAndControls/TextField.vue";
import { callApi } from "@/utils/apiClient";
import { api } from "@/api";
import {
  UsageStatsAppsRequest,
  UsageStatsAppsRowsInner,
  UsageStatsUsersRequest,
  UsageStatsUsersRowsInner,
} from "@/api/generated";
import { Getter } from "vuex-class";
import { authNamespace, AuthGetters } from "@/store/modules/auth";
import { User } from "firebase/auth";
import Heading from "@/components/commons/typography/Heading.vue";

interface UsersTable {
  [key: string]: any;
  value: keyof UsageStatsUsersRowsInner;
}

@Component({
  components: {
    Heading,
    TextField,
    SelectBox,
    AppLayout,
    SimpleTable,
    Button,
  },
})
export default class CustomersPage extends Vue {
  @Getter(`${authNamespace}/${AuthGetters.GetUser}`)
  currentUser!: User;

  resetFilters() {
    this.textSearch = null;
    this.appSearch = null;
  }

  isLoading = false;
  textSearch: string | null = null;
  appSearch: string | null = null;

  customers = [] as UsageStatsUsersRowsInner[];
  applications = [] as UsageStatsAppsRowsInner[];

  headers: Array<UsersTable> = [
    {
      text: "grids.headers.user",
      sortable: true,
      align: "start",
      value: "endUserDisplayId",
    },
    {
      text: "grids.headers.activeApps",
      sortable: true,
      align: "start",
      value: "appNames",
    },
    {
      text: "grids.headers.billing",
      sortable: true,
      align: "start",
      value: "totalCost",
    },
  ];

  async created() {
    this.isLoading = true;

    [this.customers, this.applications] = await Promise.all([
      this.fetchCustomers(),
      callApi<UsageStatsAppsRequest>(api.overview.apps, {
        timeRangeStart: new Date(0).toISOString(),
        timeRangeEnd: new Date().toISOString(),
        tenantId: this.currentUser.tenantId ?? undefined,
      }),
    ]);

    this.isLoading = false;
  }

  private fetchCustomers(
    appName?: string
  ): Promise<UsageStatsUsersRowsInner[]> {
    return callApi<UsageStatsUsersRequest>(api.customers.list, {
      timeRangeStart: new Date(0).toISOString(),
      timeRangeEnd: new Date().toISOString(),
      tenantId: this.currentUser.tenantId ?? undefined,
      appName,
    });
  }

  @Watch("appSearch")
  private async searchByApp(val: string): Promise<void> {
    if (!val) return;

    this.customers = await this.fetchCustomers(val);
  }
}
</script>

<style lang="scss" scoped>
.wrapper {
  display: grid;
  grid-template-columns: 1fr 1fr auto;
  grid-gap: 16px;
}
</style>
