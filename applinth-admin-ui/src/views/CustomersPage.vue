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
          @input="filterDebounced"
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
        <Button class="ma-auto" icon @click="resetFilters">
          <template #icon>
            <v-icon>close</v-icon>
          </template>
        </Button>
      </div>
      <SimpleTable
        class="table-medium"
        :headers="headers"
        :items="customers"
        :loading="isLoading"
        hide-footer
      >
        <template #activeAppNames="{ item }">
          {{ stringifyArray(item.activeAppNames) }}
        </template>

        <template #actions="{ item }">
          <router-link
            class="link"
            :to="{
              name: Routes.CustomerDetail,
              params: { id: item.endUserId },
            }"
          >
            <span>Detail</span>
          </router-link>
        </template>
      </SimpleTable>
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
import Heading from "@/components/commons/typography/Heading.vue";
import { Routes } from "@/enums/Routes";

interface UsersTable {
  [key: string]: any;
  value: keyof UsageStatsUsersRowsInner | "actions";
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
  Routes = Routes;

  isLoading = false;
  textSearch = "";
  lastSearchedText = "";
  appSearch = "";
  timerId: any = null;

  customers = [] as UsageStatsUsersRowsInner[];
  applications = [] as UsageStatsAppsRowsInner[];

  headers: Array<UsersTable> = [
    {
      text: this.$t("grids.headers.customer"),
      sortable: true,
      align: "start",
      value: "endUserDisplayId",
    },
    {
      text: this.$t("grids.headers.activeApplications"),
      sortable: true,
      align: "start",
      value: "activeAppNames",
    },
    {
      text: "",
      value: "actions",
    },
  ];

  async created() {
    this.isLoading = true;

    [this.customers, this.applications] = await Promise.all([
      this.fetchCustomers(),
      callApi<UsageStatsAppsRequest>(api.overview.apps, {
        timeRangeStart: new Date(0).toISOString(),
        timeRangeEnd: new Date().toISOString(),
      }),
    ]);

    this.isLoading = false;
  }

  filterDebounced() {
    clearTimeout(this.timerId);

    this.timerId = setTimeout(() => {
      this.filterByName();
    }, 700);
  }

  async resetFilters(): Promise<void> {
    let sendRequest = false;

    if (this.appSearch || this.lastSearchedText) sendRequest = true;

    this.textSearch = "";
    this.lastSearchedText = "";
    this.appSearch = "";

    if (sendRequest) {
      this.isLoading = true;
      this.customers = await this.fetchCustomers();
      this.isLoading = false;
    }
  }

  private fetchCustomers(): Promise<UsageStatsUsersRowsInner[]> {
    return callApi<UsageStatsUsersRequest>(api.customers.list, {
      timeRangeStart: new Date(0).toISOString(),
      timeRangeEnd: new Date().toISOString(),
      appId: this.appSearch,
      endUserDisplayId: this.textSearch,
      granularity: "monthly",
    });
  }

  private async filterByName(): Promise<void> {
    if (this.textSearch !== this.lastSearchedText) {
      this.isLoading = true;
      this.lastSearchedText = this.textSearch;
      this.customers = await this.fetchCustomers();
      this.isLoading = false;
    }
  }

  stringifyArray(array: Array<string> | undefined) {
    if (Array.isArray(array)) return array.join(", ");
    return "";
  }

  @Watch("appSearch")
  async searchByApp(val: string): Promise<void> {
    if (!val) return;

    this.isLoading = true;
    this.customers = await this.fetchCustomers();
    this.isLoading = false;
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
