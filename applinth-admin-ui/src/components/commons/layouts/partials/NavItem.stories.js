import NavItem from "./NavItem.vue";

export default {
  title: "Layouts/Partials/NavItem",
  component: NavItem,
};

const Template = (args, { argTypes }) => ({
  props: Object.keys(argTypes),
  components: { NavItem },
  template: '<NavItem v-bind="$props" />',
});

export const Default = Template.bind({});
Default.args = {
  navMiniVariant: false,
  label: "linkedin",
  icon: "mdi-home",
};
