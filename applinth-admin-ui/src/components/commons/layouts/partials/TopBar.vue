<template>
  <div class="d-flex align-center justify-space-between w-100">
    <router-link :to="{ name: routes.Overview }"><h1>Applinth</h1></router-link>

    <div class="d-flex align-center">
      <router-link :to="{ name: routes.Overview }">Overview</router-link>
      <router-link class="ml-2" :to="{ name: routes.Users }">Users</router-link>
      <router-link class="ml-2" :to="{ name: routes.Profile }">{{
        fullName
      }}</router-link>
      <v-btn
        class="ml-2"
        @click="handleLogout"
        icon
        color="black"
        title="Odhlásit se"
      >
        <SvgIcon iconName="logout" fill />
      </v-btn>
    </div>
  </div>
</template>

<script lang="ts">
import SvgIcon from "../../../app/SvgIcon.vue";
import { Component, Vue } from "vue-property-decorator";
import { Routes } from "@/enums";
import { Getter } from "vuex-class";
import { AuthGetters, authNamespace } from "@/store/modules/auth";
import { authService } from "@/utils";

@Component({
  components: {
    SvgIcon,
  },
})
export default class TopBar extends Vue {
  @Getter(`${authNamespace}/${AuthGetters.GetFullName}`)
  fullName!: string;

  routes = Routes;

  handleLogout() {
    authService.invalidateAuthentication(true);
  }
}
</script>

<style lang="scss" scoped>
.w-100 {
  width: 100%;
}
</style>
