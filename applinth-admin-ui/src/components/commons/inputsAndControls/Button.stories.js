import Button from "./Button.vue";

export default {
  title: "inputs and controls/Button",
  component: Button,
};

const Template = (args, { argTypes }) => ({
  props: Object.keys(argTypes),
  components: { Button },
  template: '<Button v-bind="$props">Button</Button>',
});

export const Default = Template.bind({});
Default.args = {};

export const Primary = Template.bind({});
Primary.args = {
  color: "primary",
};

export const Disabled = Template.bind({});
Disabled.args = {
  color: "primary",
  disabled: true,
};

export const Secondary = Template.bind({});
Secondary.args = {
  color: "secondary",
};

export const SecondaryOutlined = Template.bind({});
SecondaryOutlined.args = {
  color: "secondary",
  outlined: true,
};

export const Loading = Template.bind({});
Loading.args = {
  loading: true,
};

export const Small = Template.bind({});
Small.args = {
  small: true,
};

export const NoTextTransform = Template.bind({});
NoTextTransform.args = {
  color: "primary",
  noTextTransform: true,
};
