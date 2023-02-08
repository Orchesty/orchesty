<template>
  <v-navigation-drawer
    app
    clipped
    hide-overlay
    permanent
    mini-variant
    class="overflow-y-hidden"
  >
    <v-list>
      <v-list-item class="px-2" @click="redirectToDashboard">
        <v-list-item-content class="safari-fix">
          <img src="@/assets/svg/logo-small.svg" alt="HANABOSO, s.r.o." />
        </v-list-item-content>
      </v-list-item>

      <v-list-item class="px-0" @click="emitButtonClick">
        <topology-add-handler ref="topologyAddHandler" />
      </v-list-item>

      <template v-for="(item, index) in navigationItems">
        <navigation-item
          :key="item.tooltip"
          :tooltip="item.tooltip"
          :icon="item.icon"
          :to="item.to"
          :on-click="item.onClick"
        />
        <v-divider v-if="index === 8" :key="index" />
      </template>
    </v-list>
  </v-navigation-drawer>
</template>

<script>
import { ROUTES } from "@/services/enums/routerEnums"
import { ACL } from "@/services/enums/aclEnums"
import { mapActions, mapGetters } from "vuex"
import { AUTH } from "@/store/modules/auth/types"
import TopologyAddHandler from "@/components/app/topology/menu/TopologyAddHandler"
import NavigationItem from "@/components/layout/navigation/NavigationItem"
import { EVENTS, events } from "@/services/utils/events"
import { TOPOLOGIES } from "@/store/modules/topologies/types"
import { redirectTo } from "@/services/utils/utils"

export default {
  name: "Navigation",
  components: {
    NavigationItem,
    TopologyAddHandler,
  },
  computed: {
    ...mapGetters(TOPOLOGIES.NAMESPACE, {
      lastSelectedTopology: TOPOLOGIES.GETTERS.GET_LAST_SELECTED_TOPOLOGY,
    }),
  },
  data() {
    return {
      ACL: ACL,
      ROUTES: ROUTES,
      navigationItems: [
        {
          to: ROUTES.TOPOLOGY.DEFAULT,
          icon: "account_tree",
          tooltip: this.$t("navigation.topologies"),
          onClick: this.toggleSidebarAndRedirect,
        },
        {
          to: ROUTES.SCHEDULED_TASK,
          icon: "mdi-clock",
          tooltip: this.$t("navigation.scheduledTask"),
        },
        {
          to: ROUTES.APP_STORE.DEFAULT,
          icon: "apps",
          tooltip: this.$t("navigation.appStore"),
        },
        {
          to: ROUTES.LOGS,
          icon: "list_alt",
          tooltip: this.$t("navigation.logs"),
        },
        {
          to: ROUTES.HEALTH_CHECK,
          icon: "mdi-briefcase-check",
          tooltip: this.$t("navigation.healthCheck"),
        },
        {
          to: ROUTES.IMPLEMENTATION,
          icon: "mdi-cogs",
          tooltip: this.$t("navigation.implementations"),
        },
        {
          to: ROUTES.TRASH,
          icon: "delete",
          tooltip: this.$t("navigation.trash"),
        },
        {
          to: ROUTES.USER_PROFILE,
          icon: "mdi-account-circle",
          tooltip: this.$t("navigation.profile"),
        },
        {
          to: ROUTES.JWT_TOKENS,
          icon: "mdi-shield-lock-open",
          tooltip: this.$t("navigation.jwtTokens"),
        },
        {
          to: ROUTES.LOGIN,
          icon: "logout",
          tooltip: this.$t("navigation.logout"),
          onClick: this.logout,
        },
      ],
      EVENTS,
      events,
    }
  },
  methods: {
    ...mapActions(AUTH.NAMESPACE, [AUTH.ACTIONS.LOGOUT_REQUEST]),
    ...mapActions(TOPOLOGIES.NAMESPACE, [
      TOPOLOGIES.ACTIONS.TOPOLOGY.GET_BY_ID,
    ]),
    async logout() {
      await this[AUTH.ACTIONS.LOGOUT_REQUEST]()
    },
    async redirectToDashboard() {
      await this.$router.push({ name: ROUTES.DASHBOARD })
    },
    emitButtonClick() {
      this.$refs.topologyAddHandler.$refs.actionButton.click()
    },
    async toggleSidebarAndRedirect() {
      this.events.emit(EVENTS.SIDEBAR.OPEN)
      if (
        this.$router.currentRoute.name === ROUTES.TOPOLOGY.DEFAULT &&
        this.lastSelectedTopology?._id
      ) {
        await this[TOPOLOGIES.ACTIONS.TOPOLOGY.GET_BY_ID](
          this.lastSelectedTopology._id
        )
        await redirectTo(this.$router, {
          name: ROUTES.TOPOLOGY.VIEWER,
          params: { id: this.lastSelectedTopology._id },
        })
      }
    },
  },
}
</script>

<style lang="scss" scoped>
.v-list-item {
  height: 64px;
}

.safari-fix {
  max-height: 100%;

  img {
    width: 100%;
  }
}
</style>
