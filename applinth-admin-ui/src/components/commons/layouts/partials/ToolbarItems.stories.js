import ToolbarItems from "./ToolbarItems.vue";

export default {
  title: "Layouts/Partials/ToolbarItems",
  component: ToolbarItems,
};

const Template = (args, { argTypes }) => ({
  props: Object.keys(argTypes),
  components: { ToolbarItems },
  template: '<ToolbarItems v-bind="$props" />',
});

export const Default = Template.bind({});
Default.args = {};
