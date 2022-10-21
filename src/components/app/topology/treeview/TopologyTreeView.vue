<template>
  <div v-if="state.isSending" class="text-center mt-3">
    <progress-bar-linear />
  </div>
  <div v-else class="topology-tree-view-overflow">
    <v-treeview
      v-if="topologiesAll.length"
      :items="topologiesAll"
      dense
      hoverable
      activatable
      open-on-click
      item-key="_id"
      return-object
      :active.sync="active"
      :open.sync="opened"
      class="mt-3"
      @update:active="onActive"
    >
      <template #prepend="{ item, open }">
        <app-icon v-if="TOPOLOGY_ENUMS.CATEGORY === item.type">
          {{ open ? 'mdi-folder-outline' : 'mdi-folder' }}
        </app-icon>
        <app-icon v-else :color="topologyColor(item)"> account_tree </app-icon>
      </template>
      <template #label="{ item }">
        <tooltip>
          <template #activator="{ on, attrs }">
            <div class="d-flex" v-bind="attrs" v-on="on">
              <span class="truncate topology-tree-view-name">{{ item.name }}</span>
              &nbsp;
              <span>{{ topologyTitleVersion(item) }}</span>
            </div>
          </template>
          <template #tooltip>
            {{ topologyTooltip(item) }}
          </template>
        </tooltip>
      </template>
      <template #append="{ item }">
        <topology-tree-view-menu
          v-if="TOPOLOGY_ENUMS.TOPOLOGY === item.type"
          :topologies="topologiesAll"
          :topology="item"
        />
        <folder-menu v-else in :topologies="topologiesAll" :folder="item" />
      </template>
    </v-treeview>
    <div v-else class="text-center">
      <span> {{ $t('page.status.noTopologySelected') }} </span>
    </div>
  </div>
</template>

<script>
import { TOPOLOGIES } from '@/store/modules/topologies/types'
import { mapActions, mapGetters } from 'vuex'
import { REQUESTS_STATE } from '@/store/modules/api/types'
import { API } from '@/api'
import FolderMenu from '../../folder/menu/FolderMenu'
import { TOPOLOGY_ENUMS } from '@/services/enums/topologyEnums'
import { ROUTES } from '@/services/enums/routerEnums'
import TopologyTreeViewMenu from '@/components/app/topology/menu/TopologyTreeViewMenu'
import Tooltip from '@/components/commons/Tooltip'
import AppIcon from '@/components/commons/icon/AppIcon'
import ProgressBarLinear from '@/components/commons/progressIndicators/ProgressBarLinear'
import { redirectTo } from '@/services/utils/utils'

export default {
  name: 'TopologyTreeView',
  components: { ProgressBarLinear, AppIcon, Tooltip, TopologyTreeViewMenu, FolderMenu },
  data() {
    return {
      ROUTES,
      TOPOLOGY_ENUMS,
      opened: JSON.parse(localStorage.getItem(TOPOLOGY_ENUMS.TREE_VIEW)) || [],
      active: [],
      lastActive: null,
    }
  },
  computed: {
    ...mapGetters(TOPOLOGIES.NAMESPACE, {
      topologiesAll: TOPOLOGIES.GETTERS.GET_ALL_TOPOLOGIES,
      topologyActive: TOPOLOGIES.GETTERS.GET_ACTIVE_TOPOLOGY,
      lastSelectedTopology: TOPOLOGIES.GETTERS.GET_LAST_SELECTED_TOPOLOGY,
    }),
    ...mapGetters(REQUESTS_STATE.NAMESPACE, [REQUESTS_STATE.GETTERS.GET_STATE]),
    state() {
      return this[REQUESTS_STATE.GETTERS.GET_STATE]([API.topology.getList.id, API.folder.getList.id])
    },
  },
  methods: {
    ...mapActions(TOPOLOGIES.NAMESPACE, [
      TOPOLOGIES.ACTIONS.DATA.GET_TOPOLOGIES,
      TOPOLOGIES.ACTIONS.TOPOLOGY.GET_BY_ID,
      TOPOLOGIES.ACTIONS.TOPOLOGY.RESET,
    ]),
    topologyTooltip(item) {
      if (item.type === TOPOLOGY_ENUMS.CATEGORY) {
        return `${item.name}`
      }
      return `${item.name} v.${item.version}`
    },
    topologyState(item) {
      return item.visibility === TOPOLOGY_ENUMS.DRAFT ? 'draft' : item.enabled ? 'enabled' : 'disabled'
    },
    topologyColor(item) {
      return item.visibility === TOPOLOGY_ENUMS.DRAFT ? 'primary' : item.enabled ? 'success' : 'error'
    },
    topologyTitleVersion(item) {
      return TOPOLOGY_ENUMS.TOPOLOGY === item.type ? `v.${item.version}` : ''
    },
    async onActive(activeItems) {
      if (!activeItems[0]) {
        if ([ROUTES.TOPOLOGY.DEFAULT, ROUTES.TOPOLOGY.VIEWER].includes(this.$router.currentRoute.name)) {
          if (this.lastActive?._id && this.lastSelectedTopology?._id === this.lastActive._id) {
            this.active = [this.lastSelectedTopology]
            await this[TOPOLOGIES.ACTIONS.TOPOLOGY.GET_BY_ID](this.active[0]._id)
            await redirectTo(this.$router, { name: ROUTES.TOPOLOGY.VIEWER, params: { id: this.active[0]._id } })
          } else if (!this.active[0]?._id) {
            await redirectTo(this.$router, { name: ROUTES.TOPOLOGY.DEFAULT, params: { hideTopology: true } })
          }
        } else if (this.lastActive?._id) {
          this.active = [this.lastSelectedTopology]
          await this[TOPOLOGIES.ACTIONS.TOPOLOGY.GET_BY_ID](this.active[0]._id)
          await redirectTo(this.$router, { name: ROUTES.TOPOLOGY.VIEWER, params: { id: this.active[0]._id } })
        }

        this.lastActive = null
        return
      }

      if (activeItems[0].type === TOPOLOGY_ENUMS.CATEGORY) {
        return
      }

      this.lastActive = activeItems[0]

      // prevent redirect to topology when open sidebar
      if (activeItems[0]?._id === this.lastSelectedTopology?._id) return

      await this[TOPOLOGIES.ACTIONS.TOPOLOGY.GET_BY_ID](activeItems[0]._id)
      await redirectTo(this.$router, { name: ROUTES.TOPOLOGY.VIEWER, params: { id: activeItems[0]._id } })
    },
  },
  watch: {
    async $route(route) {
      if (
        (!route.matched.some(
          (matchedRoutes) => matchedRoutes.name === ROUTES.TOPOLOGY.DEFAULT || matchedRoutes.name === ROUTES.EDITOR
        ) &&
          this.active[0]) ||
        route.params.hideTopology
      ) {
        await this[TOPOLOGIES.ACTIONS.TOPOLOGY.RESET]()
      }
    },
    topologyActive: {
      deep: true,
      handler(topologyActive) {
        if (topologyActive._id) this.active = [topologyActive]
      },
    },
    topologiesAll: {
      deep: true,
      handler() {
        this.active = [this.lastSelectedTopology]
        this.lastActive = null
      },
    },
    opened: {
      deep: true,
      handler(treeview) {
        localStorage.setItem(TOPOLOGY_ENUMS.TREE_VIEW, JSON.stringify(treeview || []))
      },
    },
  },
  async created() {
    await this[TOPOLOGIES.ACTIONS.DATA.GET_TOPOLOGIES]()

    if (this.$route.params.id) {
      this.active = [{ _id: this.$route.params.id }]
    } else if (this.lastSelectedTopology?._id) {
      this.active = [this.lastSelectedTopology]
    }
  },
}
</script>

<style lang="scss" scoped>
.topology-tree-view-name {
  max-width: 210px;
  display: block;
}

.topology-tree-view-overflow {
  height: calc(100vh - 100px);
  overflow-y: auto;
}
</style>
