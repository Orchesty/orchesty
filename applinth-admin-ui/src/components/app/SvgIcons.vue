<template>
  <ul>
    <li
      v-for="icon in icons"
      :key="icon.iconName"
      class="d-flex align-center gap-4"
    >
      <SvgIcon v-bind="icon" />
      <SvgIcon v-bind="icon" class="color-accent-blue" />
      <SvgIcon v-bind="icon" class="color-main-text-50" />
      <SvgIcon v-bind="icon" class="color-main-text" small />
      <SvgIcon v-bind="icon" class="color-main-text" large />
    </li>
    <li>
      <br />
      Material Design Icon font example:
      <br />
      <span class="d-flex gap-4">
        <v-icon color="primary">mdi-close</v-icon>
        <v-icon color="secondary">mdi-close</v-icon>
        <v-icon color="default">mdi-close</v-icon>
        <v-icon small>mdi-close</v-icon>
        <v-icon large>mdi-close</v-icon>
      </span>
    </li>
  </ul>
</template>

<script lang="ts">
import { Component, Vue } from "vue-property-decorator";
import SvgIcon from "./SvgIcon.vue";

@Component({
  components: {
    SvgIcon,
  },
})
export default class SideBar extends Vue {
  icons = [
    { iconName: "dashboard", stroke: true },
    { iconName: "positions", fill: true },
    { iconName: "scheduler", stroke: true },
    { iconName: "laborers", fill: true },
    { iconName: "users", fill: true },
    { iconName: "processes", fill: true },
    { iconName: "devices", fill: true },
    { iconName: "service-done", fill: true },
    { iconName: "scheduled", fill: true },
    { iconName: "schedule", stroke: true },
    { iconName: "messages", fill: true },
    { iconName: "notifications", stroke: true },
    { iconName: "search", fill: true },
  ];
}
</script>

<style lang="scss" scoped>
ul {
  list-style: none;
  padding: 0;
}
</style>
