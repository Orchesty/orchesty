import Badge from "./Badge.vue";

export default {
  title: "Common components/Badge",
  component: Badge,
};

const Template = (args, { argTypes }) => ({
  props: Object.keys(argTypes),
  components: { Badge },
  template: '<Badge v-bind="$props" />',
});

export const Default = Template.bind({});
Default.args = {
  color: "primary",
  tooltip: "linkedin",
  icon: "linkedin",
  disableTooltip: false,
  disableIco: false,
};
