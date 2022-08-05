import SwitchField from "./SwitchField.vue";

export default {
  title: "inputs and controls/Switch Field",
  component: SwitchField,
};

const Template = (args, { argTypes }) => ({
  props: Object.keys(argTypes),
  components: { SwitchField },
  template: '<SwitchField v-bind="$props" />',
});

export const Default = Template.bind({});
Default.args = {
  name: "name",
  label: "label",
  hint: "hint",
};
