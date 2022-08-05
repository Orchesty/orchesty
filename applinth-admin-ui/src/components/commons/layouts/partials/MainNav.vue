<template>
  <v-list dense nav>
    <v-list-item-group>
      <NavItem
        :to="{ name: Routes.Home }"
        :nav-mini-variant="navMiniVariant"
        icon="mdi-home-outline"
        :label="$t('layout.appLayout.mainNav.primary.home')"
        main
        exact
      />
    </v-list-item-group>

    <v-subheader v-if="!navMiniVariant">
      {{ $t("layout.appLayout.mainNav.adminSection") }}
    </v-subheader>
    <v-divider class="my-2" v-else />

    <v-list-item-group>
      <NavItem
        :to="{ name: Routes.Home }"
        :nav-mini-variant="navMiniVariant"
        icon="mdi-account-tie-voice-outline"
        :label="$t('layout.appLayout.mainNav.secondary.home')"
      />
      <NavItemSubmenu
        :nav-mini-variant="navMiniVariant"
        :label="$t('layout.appLayout.mainNav.secondary.submenu.title')"
        icon="mdi-account-multiple-outline"
        :items="[
          {
            label: $t('layout.appLayout.mainNav.secondary.submenu.home'),
            to: { name: Routes.Home },
          },
        ]"
      />
    </v-list-item-group>
  </v-list>
</template>

<script lang="ts">
import { Component, Prop, Vue } from "vue-property-decorator";
import NavItem from "./NavItem.vue";
import NavItemSubmenu from "./NavItemSubmenu.vue";
import { Routes } from "../../../../enums";

@Component({
  components: {
    NavItem,
    NavItemSubmenu,
  },
})
export default class MainNav extends Vue {
  @Prop({ required: true, type: Boolean })
  private navMiniVariant!: boolean;

  private Routes = Routes;
}
</script>

<style lang="scss" scoped></style>
