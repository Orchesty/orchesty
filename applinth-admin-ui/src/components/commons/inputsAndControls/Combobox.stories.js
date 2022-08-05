import Combobox from "./Combobox.vue";

export default {
  title: "inputs and controls/Combobox",
  component: Combobox,
};

const Template = (args, { argTypes }) => ({
  props: Object.keys(argTypes),
  components: { Combobox },
  template: '<Combobox v-bind="$props" />',
});

export const Default = Template.bind({});
Default.args = {
  onSearch: () => {
    return ["value 1", "value 2", "value 3", "value 4"];
  },
  name: "name",
  hint: "hint",
  multiple: true,
};
