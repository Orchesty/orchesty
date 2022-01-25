<template>
  <v-navigation-drawer app clipped hide-overlay permanent mini-variant class="overflow-y-hidden">
    <v-list>
      <v-list-item class="px-2" @click="redirectToDashboard">
        <v-list-item-content class="safari-fix">
          <img src="@/assets/svg/logo.svg" alt="HANABOSO, s.r.o." />
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
        <v-divider v-if="index === 6" :key="index" />
      </template>
    </v-list>
  </v-navigation-drawer>
</template>

<script>
import { ROUTES } from '@/services/enums/routerEnums'
import { ACL } from '@/services/enums/aclEnums'
import { mapActions } from 'vuex'
import { AUTH } from '@/store/modules/auth/types'
import TopologyAddHandler from '@/components/app/topology/menu/TopologyAddHandler'
import NavigationItem from '@/components/layout/navigation/NavigationItem'

export default {
  name: 'Navigation',
  components: {
    NavigationItem,
    TopologyAddHandler,
  },
  data() {
    return {
      ACL: ACL,
      ROUTES: ROUTES,
      navigationItems: [
        { to: ROUTES.DASHBOARD, icon: 'account_tree', tooltip: this.$t('navigation.topologies') },
        { to: ROUTES.NOTIFICATION, icon: 'notifications_none', tooltip: this.$t('navigation.notifications') },
        { to: ROUTES.SCHEDULED_TASK, icon: 'access_time', tooltip: this.$t('navigation.scheduledTask') },
        { to: ROUTES.APP_STORE.DEFAULT, icon: 'apps', tooltip: this.$t('navigation.appStore') },
        { to: ROUTES.LOGS, icon: 'list_alt', tooltip: this.$t('navigation.logs') },
        { to: ROUTES.HEALTH_CHECK, icon: 'mdi-doctor', tooltip: this.$t('navigation.healthCheck') },
        { to: ROUTES.IMPLEMENTATION, icon: 'all_inbox', tooltip: this.$t('navigation.implementations') },
        { to: ROUTES.TRASH, icon: 'delete', tooltip: this.$t('navigation.trash') },
        { to: ROUTES.USER_PROFILE, icon: 'person', tooltip: this.$t('navigation.profile') },
        { to: ROUTES.LOGIN, icon: 'logout', tooltip: this.$t('navigation.logout'), onClick: this.logout },
      ],
    }
  },
  methods: {
    ...mapActions(AUTH.NAMESPACE, [AUTH.ACTIONS.LOGOUT_REQUEST]),
    async logout() {
      await this[AUTH.ACTIONS.LOGOUT_REQUEST]()
    },
    async redirectToDashboard() {
      await this.$router.push({ name: ROUTES.DASHBOARD })
    },
    emitButtonClick() {
      this.$refs.topologyAddHandler.$refs.actionButton.click()
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
