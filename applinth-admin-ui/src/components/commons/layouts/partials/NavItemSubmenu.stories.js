import NavItemSubmenu from "./NavItemSubmenu.vue";
import { Routes } from "../../../../enums";

export default {
  title: "Layouts/Partials/NavItemSubmenu",
  component: NavItemSubmenu,
};

const Template = (args, { argTypes }) => ({
  props: Object.keys(argTypes),
  components: { NavItemSubmenu },
  template: '<NavItemSubmenu v-bind="$props" />',
});

export const Default = Template.bind({});
Default.args = {
  navMiniVariant: false,
  label: "sub menu",
  icon: "mdi-home",
  items: [
    {
      label: Routes.About,
      to: Routes.About,
      show: true,
    },
    {
      label: Routes.Login,
      to: Routes.Login,
      show: false,
    },
  ],
};
