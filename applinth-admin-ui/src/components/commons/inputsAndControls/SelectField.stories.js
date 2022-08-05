import SelectField from "./SelectField.vue";

export default {
  title: "inputs and controls/Select Field",
  component: SelectField,
};

const Template = (args, { argTypes }) => ({
  props: Object.keys(argTypes),
  components: { SelectField },
  template: '<SelectField v-bind="$props" />',
});

export const Default = Template.bind({});
Default.args = {
  name: "this is a hint",
  values: [
    {
      value: "this is value",
      name: "Value 1",
    },
    {
      value: "this is value 2",
      name: "Value 2",
    },
  ],
};

export const EmptyValues = Template.bind({});

EmptyValues.args = {
  name: "this is a hint",
  values: [],
};

export const withHint = Template.bind({});

withHint.args = {
  name: "name",
  values: [
    {
      value: "this is value",
      name: "Value 1",
    },
    {
      value: "this is value 2",
      name: "Value 2",
    },
  ],
  hint: "This is a hint",
};

export const withLabel = Template.bind({});

withLabel.args = {
  name: "name",
  values: [
    {
      value: "this is value",
      name: "Value 1",
    },
    {
      value: "this is value 2",
      name: "Value 2",
    },
  ],
  label: "Label",
};
