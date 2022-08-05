import SideBar from "./SideBar.vue";

export default {
  title: "App/SideBar",
  component: SideBar,
};

const Template = (args, { argTypes }) => ({
  props: Object.keys(argTypes),
  components: { SideBar },
  template:
    '<div style="border: 1px solid darkgrey; width: 5rem;"><SideBar v-bind="$props" /></div>',
});

export const Default = Template.bind({});
Default.args = {};
