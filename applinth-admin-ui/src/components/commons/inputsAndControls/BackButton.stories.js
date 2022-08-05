import BackButton from "./BackButton.vue";
export default {
  title: "inputs and controls/BackButton",
  component: BackButton,
};

const Template = () => ({
  components: { BackButton },
  template: "<BackButton  />",
});

export const Default = Template.bind({});
Default.args = {};
