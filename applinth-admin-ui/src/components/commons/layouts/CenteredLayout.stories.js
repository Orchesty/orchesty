import CenteredLayout from "./CenteredLayout.vue";

export default {
  title: "Layouts/CenteredLayout",
  component: CenteredLayout,
};

const Template = (args, { argTypes }) => ({
  props: Object.keys(argTypes),
  components: { CenteredLayout },
  template:
    '<CenteredLayout v-bind="$props" ><h1>This is centered</h1></CenteredLayout>',
});

export const Default = Template.bind({});
Default.args = {};
