import ConfirmModal from "./ConfirmModal.vue";

export default {
  title: "Common components/ConfirmModal",
  component: ConfirmModal,
};

const Template = (args, { argTypes }) => ({
  props: Object.keys(argTypes),
  components: { ConfirmModal },
  template: '<ConfirmModal v-bind="$props" />',
});
export const Default = Template.bind({});
Default.args = {
  value: true,
  title: "asks",
  isOpen: true,
};
