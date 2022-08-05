import Logo from "./Logo.vue";

export default {
  title: "App/Logo",
  component: Logo,
};

const Template = (args, { argTypes }) => ({
  props: Object.keys(argTypes),
  components: { Logo },
  template: '<Logo v-bind="$props" />',
});

export const Default = Template.bind({});
Default.args = {};
