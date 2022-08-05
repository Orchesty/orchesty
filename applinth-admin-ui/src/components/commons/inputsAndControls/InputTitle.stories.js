import InputTitle from "./InputTitle.vue";

export default {
  title: "inputs and controls/InputTitle",
  component: InputTitle,
};

const Template = (args, { argTypes }) => ({
  props: Object.keys(argTypes),
  components: { InputTitle },
  template: '<InputTitle v-bind="$props" />',
});

export const Default = Template.bind({});
Default.args = {
  tooltip: "this is a hint",
};
