<template>
  <div class="wrapper d-flex justify-space-between align-center ml-4">
    <div class="d-flex align-center">
      <router-link class="d-flex align-center logo-link" to="/">
        <img
          class="logo-image"
          alt="Applinth Logo"
          :src="require('@/assets/img/logo.svg')"
        />
        <span class="logo-text">Applinth Admin</span>
      </router-link>
    </div>
    <nav class="d-flex align-center text-body-2 gap-4">
      <router-link :to="{ name: routes.Overview }">Overview</router-link>
      <router-link :to="{ name: routes.Customers }">Customers</router-link>
      <router-link :to="{ name: routes.Users }">Users</router-link>
      <router-link
        class="ml-8 mr-4"
        :to="{ name: routes.Profile }"
        title="Profile"
        >{{ displayName }}</router-link
      >
      <button
        class="d-flex align-center"
        @click="handleLogout"
        title="Odhlásit se"
      >
        <SvgIcon class="color-white" iconName="logout" fill />
      </button>
    </nav>
  </div>
</template>

<script lang="ts">
import SvgIcon from "../../../app/SvgIcon.vue";
import { Component, Vue } from "vue-property-decorator";
import { Routes } from "../../../../enums/Routes";
import { Getter } from "vuex-class";
import {
  AuthGetters,
  authNamespace,
} from "../../../../store/modules/auth/types";
import { invalidateAuthentication } from "../../../../utils/authService";

@Component({
  components: {
    SvgIcon,
  },
})
export default class TopBar extends Vue {
  @Getter(`${authNamespace}/${AuthGetters.GetDisplayName}`)
  displayName!: string;

  routes = Routes;

  handleLogout() {
    invalidateAuthentication();
  }
}
</script>

<style lang="scss" scoped>
.wrapper {
  width: 100%;
}

.logo-image {
  max-height: 36px;
  width: auto;
}

.logo-link {
  color: $color-white;
  text-decoration: none;
  font-size: 20px;
  font-weight: 600;
}

.logo-text {
  margin-left: 1rem;
}

nav a {
  text-decoration: none;
  color: $color-white;
}
</style>
