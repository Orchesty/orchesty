import Alert from "./Alert.vue";

export default {
  title: "alerts/Alert",
  component: Alert,
};

const Template = (args, { argTypes }) => ({
  props: Object.keys(argTypes),
  components: { Alert },
  template: '<Alert v-bind="$props" />',
});

export const Error = Template.bind({});
Error.args = {
  alert: {
    message: "Some message",
    type: "ERROR",
    id: "1",
    timeout: 5000,
  },
};

export const Info = Template.bind({});
Info.args = {
  alert: {
    message: "Some message",
    type: "INFO",
    id: "1",
    timeout: 5000,
  },
};

export const Success = Template.bind({});
Success.args = {
  alert: {
    message: "Some message",
    type: "SUCCESS",
    id: "1",
    timeout: 5000,
  },
};
