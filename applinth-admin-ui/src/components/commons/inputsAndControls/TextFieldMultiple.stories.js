import TextFieldMultiple from "./TextFieldMultiple.vue";

export default {
  title: "inputs and controls/TextFieldMultiple",
  component: TextFieldMultiple,
};

const Template = (args, { argTypes }) => ({
  props: Object.keys(argTypes),
  components: { TextFieldMultiple },
  template: '<TextFieldMultiple v-bind="$props" />',
});

export const Default = Template.bind({});
Default.args = {
  label: "Text",
  name: "name",
};
