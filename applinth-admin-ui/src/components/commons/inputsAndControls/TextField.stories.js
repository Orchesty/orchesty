import TextField from "./TextField.vue";

export default {
  title: "inputs and controls/TextField",
  component: TextField,
};

const Template = (args, { argTypes }) => ({
  props: Object.keys(argTypes),
  components: { TextField },
  template: '<TextField v-bind="$props" name=""/>',
});

export const Text = Template.bind({});
Text.args = {
  label: "Text",
  name: "name",
  type: "text",
};

export const Number = Template.bind({});
Number.args = {
  ...Text.args,
  label: "Number",
  type: "number",
};

export const Password = Template.bind({});
Password.args = {
  ...Text.args,
  label: "Password",
  type: "password",
};

export const Disabled = Template.bind({});
Disabled.args = {
  label: "Text",
  type: "text",
  disabled: true,
};

export const HideDetails = Template.bind({});
HideDetails.args = {
  label: "Text",
  type: "text",
  hideDetails: true,
};

export const PrependInnerIcon = Template.bind({});
PrependInnerIcon.args = {
  label: "Text",
  type: "text",
  prependInnerIcon: "mdi-magnify",
};
