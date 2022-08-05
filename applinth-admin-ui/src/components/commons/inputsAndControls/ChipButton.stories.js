import ChipButton from "./ChipButton.vue";

export default {
  title: "inputs and controls/ChipButton",
  component: ChipButton,
};

const Template = (args, { argTypes }) => ({
  props: Object.keys(argTypes),
  components: { ChipButton },
  template: '<ChipButton v-bind="$props">Chip Button</ChipButton>',
});

export const Default = Template.bind({});
Default.args = {};

export const Active = Template.bind({});
Active.args = {
  active: true,
};
