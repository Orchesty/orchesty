import TopBar from "./TopBar.vue";

export default {
  title: "App/TopBar",
  component: TopBar,
};

const Template = (args, { argTypes }) => ({
  props: Object.keys(argTypes),
  components: { TopBar },
  template: `<div
    style="border: 1px solid darkgrey; width: 80%; height: 2rem; display: grid; place-items: center;"
    >
    <TopBar v-bind="$props" />
  </div>`,
});

export const Default = Template.bind({});
Default.args = {};
