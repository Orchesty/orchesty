import TableHeader from "./TableHeader.vue";
import Button from "../inputsAndControls/Button";
import { TablesNamespaces } from "../../../store/modules/tables";
export default {
  title: "Tables/TableHeader",
  component: TableHeader,
  subcomponents: { Button },
};

const Template = (args, { argTypes }) => ({
  props: Object.keys(argTypes),
  components: { TableHeader, Button },
  template: `<TableHeader v-bind="$props">
    <template #actions>
      <Button>Action</Button>
    </template>
  </TableHeader>`,
});

export const Default = Template.bind({});
Default.args = {
  tableOptions: {
    namespace: TablesNamespaces.ExampleTable,
  },
  disableFilter: true,
};
