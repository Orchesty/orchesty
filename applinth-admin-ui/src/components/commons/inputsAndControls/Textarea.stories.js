import TextArea from "./Textarea.vue";

export default {
  title: "inputs and controls/TextArea",
  component: TextArea,
};

const Template = (args, { argTypes }) => ({
  props: Object.keys(argTypes),
  components: { TextArea },
  template: '<TextArea v-bind="$props" name=""/>',
});

export const Default = Template.bind({});
Default.args = {
  label: "Text",
  name: "name",
};
