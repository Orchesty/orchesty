import SelectBox from "./SelectBox.vue";

export default {
  title: "inputs and controls/SelectBox",
  component: SelectBox,
};

const Template = (args, { argTypes }) => ({
  props: Object.keys(argTypes),
  components: { SelectBox },
  template: '<SelectBox v-bind="$props"  :items="[]" name=""/>',
});

export const Default = Template.bind({});
Default.args = {
  label: "Text",
  name: "name",
};

export const Inverted = Template.bind({});
Inverted.args = {
  label: "Text",
  name: "name",
  soloInverted: true,
};
