import SideBarItem from "./SideBarItem.vue";

export default {
  title: "App/SideBarItem",
  component: SideBarItem,
};

const Template = (args, { argTypes }) => ({
  props: Object.keys(argTypes),
  components: { SideBarItem },
  template: '<SideBarItem v-bind="$props" />',
});

export const Default = Template.bind({});
Default.args = {
  text: "Homepage",
  iconName: "dashboard",
  to: "/",
  stroke: "true",
};
