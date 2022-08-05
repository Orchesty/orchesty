<template>
  <div>
    <v-app-bar
      app
      clipped
      permanent
      height="54px"
      class="elevation-1 bg-color-white"
    >
      <TopBar />
    </v-app-bar>

    <v-main>
      <v-container>
        <slot />
      </v-container>
    </v-main>
  </div>
</template>

<script lang="ts">
import { Component, Vue } from "vue-property-decorator";
import MainNav from "./partials/MainNav.vue";
import TopBar from "./partials/TopBar.vue";
import Toolbar from "./partials/Toolbar.vue";

@Component({
  components: {
    MainNav,
    Toolbar,
    TopBar,
  },
})
export default class AdminLayout extends Vue {}
</script>
