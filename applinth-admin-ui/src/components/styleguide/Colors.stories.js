import Colors from "./Colors.vue";

export default {
  title: "Styleguide/Colors",
  component: Colors,
};

const Template = (args, { argTypes }) => ({
  props: Object.keys(argTypes),
  components: { Colors },
  template: '<Colors v-bind="$props" />',
});

export const Default = Template.bind({});
