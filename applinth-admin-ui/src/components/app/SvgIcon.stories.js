import SvgIcons from "./SvgIcons.vue";

export default {
  title: "Styleguide/SvgIcons",
  component: SvgIcons,
};

const Template = (args, { argTypes }) => ({
  props: Object.keys(argTypes),
  components: { SvgIcons },
  template: '<SvgIcons v-bind="$props" />',
});

export const Default = Template.bind({});
Default.args = {};
