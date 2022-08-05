import RoundButton from "./RoundButton.vue";

export default {
  title: "inputs and controls/RoundButton",
  component: RoundButton,
};

const Template = (args, { argTypes }) => ({
  props: Object.keys(argTypes),
  components: { RoundButton },
  template: '<RoundButton v-bind="$props">Button Chip</RoundButton>',
});

export const Close = Template.bind({});
Close.args = {
  icon: "close",
};

export const Edit = Template.bind({});
Edit.args = {
  icon: "pencil",
};

export const Add = Template.bind({});
Add.args = {
  icon: "plus",
};
