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
        />
        <Button class="ma-auto" icon min-width="0" @click="resetFilters">
          <v-icon>close</v-icon>
        </Button>
      </div>
      <SimpleTable class="table-medium" :headers="headers" :items="customers" />
    </div>
  </AppLayout>
</template>

<script lang="ts">
import { Component, Vue } from "vue-property-decorator";
import SimpleTable from "@/components/commons/tables/SimpleTable.vue";
import AppLayout from "../components/commons/layouts/AppLayout.vue";
import Button from "../components/commons/inputsAndControls/Button.vue";
import SelectBox from "@/components/commons/inputsAndControls/SelectBox.vue";
import TextField from "@/components/commons/inputsAndControls/TextField.vue";
import { callApi } from "@/utils/apiClient";
import { api } from "@/api";
import {
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
  textSearch = null;
  appSearch = null;

  customers = [] as UsageStatsUsersRowsInner[];

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
    this.customers = await callApi<UsageStatsUsersRequest>(api.customers.list, {
      timeRangeStart: new Date(0).toISOString(),
      timeRangeEnd: new Date().toISOString(),
      tenantId: this.currentUser.tenantId ?? undefined,
    });
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
