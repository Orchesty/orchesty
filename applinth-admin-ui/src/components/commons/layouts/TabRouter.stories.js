import TabRouter from "./TabRouter.vue";
import { Routes } from "../../../enums";

/**
 * Issue viewing this component in Storybook
 * https://github.com/shentao/vue-multiselect/issues/966
 */

export default {
  title: "Common components/TabRouter",
  component: TabRouter,
};

const Template = (args, { argTypes }) => ({
  props: Object.keys(argTypes),
  components: { TabRouter },
  template: '<TabRouter v-bind="$props" />',
});

export const Default = Template.bind({});
Default.args = {
  items: [
    {
      title: Routes.About,
      to: Routes.About,
      error: "",
    },
    {
      title: Routes.Login,
      to: Routes.Login,
      error: "",
    },
  ],
};
