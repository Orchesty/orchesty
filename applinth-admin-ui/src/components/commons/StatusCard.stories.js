import StatusCard from "./StatusCard.vue";

export default {
  title: "App/StatusCard",
  component: StatusCard,
};

const Template = (args, { argTypes }) => ({
  props: Object.keys(argTypes),
  components: { StatusCard },
  template: '<StatusCard v-bind="$props" />',
});

export const Primary = Template.bind({});
Primary.args = {
  score: 50,
  title: "ZAKAZKY",
  badge: 20,
};

export const Secondary = Template.bind({});
Secondary.args = {
  score: 80,
  title: "PRIPNUTE",
};

export const Error = Template.bind({});
Error.args = {
  score: 12,
  title: "PROBLÉM",
  hasError: true,
};
