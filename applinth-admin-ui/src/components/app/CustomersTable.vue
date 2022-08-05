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
export default class CustomersTable extends Vue {
  tableOptions: TableOptions<any, any> = {
    defaultSortBy: ["surname"],
    headers: [
      {
        text: "User",
        sortable: true,
        align: "start",
        value: "user",
      },
      {
        text: "Active Apps",
        sortable: true,
        align: "start",
        value: "activeApps",
      },
      {
        text: "Billing",
        sortable: true,
        align: "start",
        value: "billing",
      },
    ],
    namespace: TablesNamespaces.CustomersTable,
  };
}
</script>
