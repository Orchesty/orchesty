import MainNav from "./MainNav.vue";

export default {
  title: "Layouts/Partials/MainNav",
  component: MainNav,
};

const Template = (args, { argTypes }) => ({
  props: Object.keys(argTypes),
  components: { MainNav },
  template: '<MainNav v-bind="$props" />',
});

export const Default = Template.bind({});
Default.args = {
  color: "primary",
  tooltip: "linkedin",
  icon: "linkedin",
  disableTooltip: false,
  disableIco: false,
};
