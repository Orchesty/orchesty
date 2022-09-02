<template>
  <div>
    <v-app-bar app color="primary">
      <router-link class="d-flex align-center" to="/">
        <v-img
          alt="Applinth Logo"
          contain
          min-width="100"
          src="../../../assets/svg/logo.svg"
          width="100"
        />
      </router-link>

      <v-spacer />

      <navigation-item
        v-for="item in navigationItems"
        :key="item.text"
        class="navigation-item"
        :text="item.text"
        :icon="item.icon"
        :to="item.to"
      />
    </v-app-bar>

    <v-main>
      <v-container>
        <v-row>
          <v-col>
            <v-breadcrumbs :items="breadCrumbs" class="px-0">
              <template #item="{ item }">
                <v-breadcrumbs-item
                  :to="item.to"
                  :disabled="item.disabled"
                  exact
                >
                  {{ $t(item.text) }}
                </v-breadcrumbs-item>
              </template>
            </v-breadcrumbs>
          </v-col>
        </v-row>
        <slot></slot>
      </v-container>
    </v-main>
  </div>
</template>

<script lang="ts">
import { Component, Vue } from "vue-property-decorator";
import NavigationItem from "@/components/app/NavigationItem.vue";
import { Routes } from "@/enums";
@Component({
  components: { NavigationItem },
})
export default class AppLayout extends Vue {
  navigationItems = [
    {
      to: Routes.Overview,
      icon: "mdi-toy-brick",
      text: "navigation.link.overview",
    },
    {
      to: Routes.Customers,
      icon: "mdi-apps",
      text: "navigation.link.customers",
    },
    {
      to: Routes.Profile,
      icon: "mdi-delete",
      text: "navigation.link.profile",
    },
    {
      to: Routes.Users,
      icon: "mdi-account-cog",
      text: "navigation.link.users",
    },
  ];

  currentAppName: null | string = null;

  get breadCrumbs() {
    if (this.$route.meta) {
      if (typeof this.$route.meta.breadcrumbs === "function") {
        return this.$route.meta.breadcrumbs(this.currentAppName);
      }
      return this.$route.meta.breadcrumbs;
    } else {
      return null;
    }
  }
}
</script>
