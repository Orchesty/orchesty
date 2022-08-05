import { Component, Vue } from "vue-property-decorator";

@Component
export default class MobileMixin extends Vue {
  protected isMobile(): boolean {
    return (
      this.$vuetify.breakpoint.name === "sm" ||
      this.$vuetify.breakpoint.name === "xs"
    );
  }
}
