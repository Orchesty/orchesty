<template>
  <v-list>
    <v-list-item @click="profile">
      <v-list-item-content>
        <v-list-item-title>{{
          $t("layout.appLayout.toolbar.profile")
        }}</v-list-item-title>
      </v-list-item-content>
    </v-list-item>
    <v-list-item @click="logout">
      <v-list-item-content>
        <v-list-item-title>{{
          $t("layout.appLayout.toolbar.logout")
        }}</v-list-item-title>
      </v-list-item-content>
    </v-list-item>
  </v-list>
</template>

<script lang="ts">
import { Component, Vue } from "vue-property-decorator";
import { invalidateAuthentication } from "@/utils/authService";
import { router } from "../../../../utils/router";
import { Routes } from "../../../../enums";

@Component({
  components: {},
})
export default class ToolbarItems extends Vue {
  logout() {
    invalidateAuthentication();
  }

  profile() {
    if (router.currentRoute.name !== Routes.Profile) {
      router.push({ name: Routes.Profile });
    }
  }
}
</script>
