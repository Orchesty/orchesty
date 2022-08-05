import Card from "./Card.vue";

export default {
  title: "Common components/Card",
  component: Card,
};

const Template = (args, { argTypes }) => ({
  props: Object.keys(argTypes),
  components: { Card },
  template:
    '<Card v-bind="$props"><div class="pa-5">some content...</div></Card>',
});

export const Default = Template.bind({});
Default.args = {};

export const Loading = Template.bind({});
Loading.args = {
  loading: true,
};

export const FullHeight = Template.bind({});
FullHeight.args = {
  height: "100%",
};

export const ExpandableCardId = Template.bind({});
ExpandableCardId.args = {
  expandableCardId: "some-card-id",
};
