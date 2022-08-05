import Autocomplete from "./Autocomplete.vue";

export default {
  title: "inputs and controls/Autocomplete",
  component: Autocomplete,
};

const Template = (args, { argTypes }) => ({
  props: Object.keys(argTypes),
  components: { Autocomplete },
  template: '<Autocomplete v-bind="$props" />',
});

export const Default = Template.bind({});
Default.args = {
  name: "name",
  value: "value",
  items: [
    {
      value: "value 1",
      text: "this is text",
    },
    {
      value: "value 2",
      text: "this is text 2",
    },
  ],
};
