<template>
  <v-navigation-drawer app clipped hide-overlay permanent mini-variant class="overflow-y-hidden">
    <v-list class="py-0">
      <v-list-item class="px-2 sidebar-logo" @click="redirectDashboard">
        <v-list-item-content>
          <img class="sidebar-logo" src="@/assets/svg/logo.svg" alt="HANABOSO, s.r.o." />
        </v-list-item-content>
      </v-list-item>
    </v-list>
    <v-list>
      <v-list-item class="px-0 font-weight-bold" @click="emitClick">
        <topology-add-handler ref="topologyAddHandler" />
      </v-list-item>
      <tooltip orientation="right">
        <template #activator="{ on, attrs }">
          <v-list-item v-bind="attrs" :to="{ name: ROUTES.TOPOLOGIES.DEFAULT }" v-on="on">
            <v-list-item-content>
              <v-icon> account_tree </v-icon>
            </v-list-item-content>
          </v-list-item>
        </template>
        <template #tooltip>
          <span class="font-weight-medium"> {{ $t('navigation.topologies') }} </span>
        </template>
      </tooltip>

      <tooltip orientation="right">
        <template #activator="{ on, attrs }">
          <v-list-item v-bind="attrs" :to="{ name: ROUTES.NOTIFICATIONS }" v-on="on">
            <v-list-item-content>
              <v-icon> notifications_none </v-icon>
            </v-list-item-content>
          </v-list-item>
        </template>
        <template #tooltip>
          <span class="font-weight-medium"> {{ $t('navigation.notifications') }} </span>
        </template>
      </tooltip>

      <tooltip orientation="right">
        <template #activator="{ on, attrs }">
          <v-list-item v-bind="attrs" :to="{ name: ROUTES.SCHEDULED_TASK }" v-on="on">
            <v-list-item-content>
              <v-icon> access_time </v-icon>
            </v-list-item-content>
          </v-list-item>
        </template>
        <template #tooltip>
          <span class="font-weight-medium"> {{ $t('navigation.scheduledTask') }} </span>
        </template>
      </tooltip>

      <tooltip orientation="right">
        <template #activator="{ on, attrs }">
          <v-list-item v-bind="attrs" :to="{ name: ROUTES.APP_STORE.DEFAULT }" v-on="on">
            <v-list-item-content>
              <v-icon> apps </v-icon>
            </v-list-item-content>
          </v-list-item>
        </template>
        <template #tooltip>
          <span class="font-weight-medium"> {{ $t('navigation.appStore') }} </span>
        </template>
      </tooltip>

      <tooltip orientation="right">
        <template #activator="{ on, attrs }">
          <v-list-item :to="{ name: ROUTES.LOGS }" v-bind="attrs" v-on="on">
            <v-list-item-content>
              <v-icon> list_alt </v-icon>
            </v-list-item-content>
          </v-list-item>
        </template>
        <template #tooltip>
          <span class="font-weight-medium"> {{ $t('navigation.logs') }} </span>
        </template>
      </tooltip>

      <tooltip orientation="right">
        <template #activator="{ on, attrs }">
          <v-list-item v-bind="attrs" :to="{ name: ROUTES.IMPLEMENTATIONS }" v-on="on">
            <v-list-item-content>
              <v-icon> all_inbox </v-icon>
            </v-list-item-content>
          </v-list-item>
        </template>
        <template #tooltip>
          <span class="font-weight-medium"> {{ $t('navigation.implementations') }} </span>
        </template>
      </tooltip>

      <tooltip orientation="right">
        <template #activator="{ on, attrs }">
          <v-list-item :to="{ name: ROUTES.TRASH }" v-bind="attrs" v-on="on">
            <v-list-item-content>
              <v-icon> delete </v-icon>
            </v-list-item-content>
          </v-list-item>
        </template>
        <template #tooltip>
          <span class="font-weight-medium"> {{ $t('navigation.trash') }} </span>
        </template>
      </tooltip>

      <tooltip orientation="right">
        <template #activator="{ on, attrs }">
          <v-list-item v-if="$can('read', ACL.USERS_PAGE)" v-bind="attrs" :to="{ name: ROUTES.USERS }" v-on="on">
            <v-list-item-content>
              <v-icon> person_add </v-icon>
            </v-list-item-content>
          </v-list-item>
        </template>
        <template #tooltip>
          <span class="font-weight-medium"> {{ $t('navigation.users') }} </span>
        </template>
      </tooltip>
    </v-list>
    <v-divider />
    <v-list-item v-if="name">
      <v-list-item-title>{{ name }}</v-list-item-title>
    </v-list-item>
    <v-divider v-if="name" />

    <tooltip orientation="right">
      <template #activator="{ on, attrs }">
        <v-list-item v-bind="attrs" :to="{ name: ROUTES.USER_PROFILE }" v-on="on">
          <v-list-item-content>
            <v-icon> person </v-icon>
          </v-list-item-content>
        </v-list-item>
      </template>
      <template #tooltip>
        <span class="font-weight-medium"> {{ $t('navigation.profile') }} </span>
      </template>
    </tooltip>

    <tooltip orientation="right">
      <template #activator="{ on, attrs }">
        <v-list-item v-bind="attrs" link v-on="on" @click="logout">
          <v-list-item-content>
            <v-icon> logout </v-icon>
          </v-list-item-content>
        </v-list-item>
      </template>
      <template #tooltip>
        <span class="font-weight-medium"> {{ $t('navigation.logout') }} </span>
      </template>
    </tooltip>
    <modal-create-folder />
    <modal-create-topology />
    <modal-topology-import />
  </v-navigation-drawer>
</template>

<script>
import { ROUTES } from '../../../router/routes'
import { ACL } from '../../../enums'
import { mapActions, mapState } from 'vuex'
import { AUTH } from '../../../store/modules/auth/types'
import TopologyAddHandler from '@/components/app/topology/menu/TopologyAddHandler'
import ModalCreateFolder from '@/components/app/folder/modal/ModalCreateFolder'
import ModalCreateTopology from '@/components/app/topology/modal/ModalCreateTopology'
import ModalTopologyImport from '@/components/app/topology/modal/ModalImportTopology'
import Tooltip from '@/components/commons/tooltip/Tooltip'

export default {
  name: 'Navigation',
  components: { Tooltip, ModalTopologyImport, ModalCreateTopology, ModalCreateFolder, TopologyAddHandler },
  data() {
    return {
      ACL: ACL,
      ROUTES: ROUTES,
      dropDownMenu: false,
    }
  },
  computed: {
    ...mapState(AUTH.NAMESPACE, ['user']),
    name() {
      if (this.user) {
        return this.user.email
      }

      return null
    },
  },
  methods: {
    ...mapActions(AUTH.NAMESPACE, [AUTH.ACTIONS.LOGOUT_REQUEST]),
    logout() {
      this[AUTH.ACTIONS.LOGOUT_REQUEST]()
    },
    emitClick() {
      this.$refs.topologyAddHandler.$refs.actionButton.click()
    },
    redirectDashboard() {
      this.$router.push({ name: ROUTES.TOPOLOGIES.DEFAULT })
    },
  },
}
</script>

<style lang="scss" scoped>
.sidebar-logo {
  align-self: center;
  max-width: 100%;
}
.sidebar-logo-backgroud {
  background: var(--v-primary-base) !important;
}
.item-active {
  background: var(--v-primary-base) !important;
  border-bottom-right-radius: 0.3em;
  border-top-right-radius: 0.3em;
  color: var(--v-white-base) !important;
}
.v-list-item {
  height: 64px;
}
</style>
