import Typography from "./Typography.vue";

export default {
  title: "Styleguide/Typography",
  component: Typography,
};

const Template = (args, { argTypes }) => ({
  props: Object.keys(argTypes),
  components: { Typography },
  template: '<Typography v-bind="$props" />',
});

export const Default = Template.bind({});
