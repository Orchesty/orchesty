import Table from "./Table.vue";
import { TablesNamespaces } from "../../../store/modules/tables";

export default {
  title: "Tables/Table",
  component: Table,
};

const Template = (args, { argTypes }) => ({
  props: Object.keys(argTypes),
  components: { Table },
  template: '<Table v-bind="$props"/>',
});

export const Default = Template.bind({});
Default.args = {
  tableOptions: {
    namespace: TablesNamespaces.ExampleTable,
  },
};
