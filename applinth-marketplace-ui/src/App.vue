<template>
  <v-app>
    <flash-messages />

    <v-app-bar app color="primary">
      <router-link class="d-flex align-center" to="/">
        <v-img
          alt="Applinth Logo"
          contain
          min-width="100"
          :src="loadLogo()"
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

    <v-main style="padding-bottom: 60px">
      <v-container class="wrapper">
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
        <router-view @appChanged="onAppChanged" />
      </v-container>
    </v-main>
    <v-footer absolute inset app width="auto" class="pb-1">
      <v-container>
        <v-row justify="center">
          <v-col
            lg="2"
            md="3"
            cols="12"
            align-self="center"
            class="text--secondary"
          >
            Powered by
            <a
              target="_blank"
              rel="noopener noreferrer"
              href="https://applinth.io"
              class="text--secondary"
            >
              Applinth
            </a>
          </v-col>
        </v-row>
      </v-container>
    </v-footer>
  </v-app>
</template>

<script>
import { ROUTES } from "@/router/routes"
import NavigationItem from "@/components/commons/NavigationItem"
import FlashMessages from "@/components/commons/FlashMessages"

export default {
  name: "App",
  components: { FlashMessages, NavigationItem },
  computed: {
    breadCrumbs() {
      if (typeof this.$route.meta.breadcrumbs === "function") {
        return this.$route.meta.breadcrumbs(this.currentAppName)
      }
      return this.$route.meta.breadcrumbs
    },
  },
  data: () => ({
    ROUTES,
    navigationItems: [
      {
        to: ROUTES.OVERVIEW,
        icon: "mdi-toy-brick",
        text: "navigation.link.integrations",
      },
      {
        to: ROUTES.APPLICATIONS,
        icon: "mdi-apps",
        text: "navigation.link.applications",
      },
      {
        to: ROUTES.LOGS,
        icon: "mdi-list-box-outline",
        text: "navigation.link.logs",
      },
      {
        to: ROUTES.TRASH,
        icon: "mdi-delete",
        text: "navigation.link.trash",
      },
      {
        to: ROUTES.SETTINGS,
        icon: "mdi-account-cog",
        text: "navigation.link.settings",
      },
    ],
    currentAppName: null,
  }),
  methods: {
    onAppChanged(name) {
      this.currentAppName = name
    },

    loadLogo() {
      return require("@/assets/svg/logo.svg")
    },
  },
}
</script>

<style scoped lang="scss">
.navigation-item:not(:first-child) {
  margin-left: 1.2rem;
}
</style>
