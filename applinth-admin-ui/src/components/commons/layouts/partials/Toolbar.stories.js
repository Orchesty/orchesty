import Toolbar from "./Toolbar.vue";

export default {
  title: "Layouts/Partials/Toolbar",
  component: Toolbar,
};

const Template = (args, { argTypes }) => ({
  props: Object.keys(argTypes),
  components: { Toolbar },
  template: '<Toolbar v-bind="$props" />',
});

export const Default = Template.bind({});
Default.args = {};
