import ColorPickerInput from "./ColorPickerInput.vue";

export default {
  title: "inputs and controls/ColorPickerInput",
  component: ColorPickerInput,
};

const Template = (args, { argTypes }) => ({
  props: Object.keys(argTypes),
  components: { ColorPickerInput },
  template: '<ColorPickerInput v-bind="$props" />',
});

export const Default = Template.bind({});
Default.args = {
  value: "#0000",
  placeholder: "Pick Colors",
};
