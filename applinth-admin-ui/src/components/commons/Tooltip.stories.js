import Tooltip from "./Tooltip.vue";

/**
 * Issue viewing this component in Storybook
 * https://github.com/shentao/vue-multiselect/issues/966
 */

export default {
  title: "Common components/Tooltip",
  component: Tooltip,
};

const Template = (args, { argTypes }) => ({
  props: Object.keys(argTypes),
  components: { Tooltip },
  template: '<Tooltip v-bind="$props" />',
});

export const Default = Template.bind({});
Default.args = {
  text: "text",
};
