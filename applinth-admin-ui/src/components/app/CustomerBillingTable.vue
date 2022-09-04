<template>
  <Table :table-options="tableOptions" />
</template>

<script lang="ts">
import { Component, Vue } from "vue-property-decorator";
import { TableOptions } from "@/components/commons/tables/types";
import { TablesNamespaces } from "@/store/modules/tables";
import Table from "@/components/commons/tables/Table.vue";

@Component({
  components: {
    Table,
  },
})
export default class CustomerBillingTable extends Vue {
  tableOptions: TableOptions<any, any> = {
    defaultSortBy: ["month"],
    headers: [
      {
        text: "grids.headers.month",
        sortable: true,
        align: "start",
        value: "month",
      },
      {
        text: "grids.headers.application",
        sortable: true,
        align: "start",
        value: "application",
      },
      {
        text: "grids.headers.billing",
        sortable: true,
        align: "start",
        value: "billing",
      },
    ],
    namespace: TablesNamespaces.CustomersBillingTable,
  };
}
</script>
