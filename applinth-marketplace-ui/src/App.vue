<template>
  <v-app>
    <flash-messages />

    <v-app-bar app color="primary">
      <router-link class="d-flex align-center" to="/">
        <v-img
          alt="Applinth Logo"
          contain
          min-width="100"
          src="./assets/svg/logo.svg"
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
        <router-view />
      </v-container>
    </v-main>
  </v-app>
</template>

<script>
import { ROUTES } from '@/router/routes'
import FlashMessages from '@/components/commons/FlashMessages'
import NavigationItem from '@/components/commons/NavigationItem'

export default {
  name: 'App',
  components: { NavigationItem, FlashMessages },
  computed: {
    breadCrumbs() {
      return this.$route.meta.breadcrumbs
    },
  },
  data: () => ({
    ROUTES,
    navigationItems: [
      {
        to: ROUTES.OVERVIEW,
        icon: 'mdi-toy-brick',
        text: 'navigation.link.integrations',
      },
      {
        to: ROUTES.APPLICATIONS,
        icon: 'mdi-apps',
        text: 'navigation.link.applications',
      },
      {
        to: ROUTES.TRASH,
        icon: 'mdi-delete',
        text: 'navigation.link.trash',
      },
      {
        to: ROUTES.SETTINGS,
        icon: 'mdi-account-cog',
        text: 'navigation.link.settings',
      },
    ],
  }),
}
</script>

<style scoped lang="scss">
.navigation-item:not(:first-child) {
  margin-left: 1.2rem;
}
</style>
