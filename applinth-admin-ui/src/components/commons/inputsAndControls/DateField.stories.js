import DateField from "./DateField.vue";

export default {
  title: "inputs and controls/DateField",
  component: DateField,
};

const Template = (args, { argTypes }) => ({
  props: Object.keys(argTypes),
  components: { DateField },
  template: '<DateField v-bind="$props" />',
});

export const Default = Template.bind({});
Default.args = {
  label: "label",
  name: "name",
  hint: "hint",
};
