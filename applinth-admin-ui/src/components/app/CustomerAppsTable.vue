<template>
  <Table :table-options="tableOptions" />
</template>

<script lang="ts">
import { Component, Vue } from "vue-property-decorator";
import SelectBox from "@/components/commons/inputsAndControls/SelectBox.vue";
import TextField from "@/components/commons/inputsAndControls/TextField.vue";
import { TableOptions } from "@/components/commons/tables/types";
import { TablesNamespaces } from "@/store/modules/tables";
import Table from "@/components/commons/tables/Table.vue";

@Component({
  components: {
    Table,
    TextField,
    SelectBox,
  },
})
export default class CustomerAppsTable extends Vue {
  tableOptions: TableOptions<any, any> = {
    defaultSortBy: ["installed"],
    headers: [
      {
        text: "Active apps",
        sortable: true,
        align: "start",
        value: "activeApps",
      },
      {
        text: "Installed",
        sortable: true,
        align: "start",
        value: "installed",
      },
    ],
    namespace: TablesNamespaces.CustomersAppsTable,
  };
}
</script>
