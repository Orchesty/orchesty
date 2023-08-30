<template>
  <div v-if="state.isSending" class="text-center">
    <progress-bar-circular />
  </div>
  <div v-else class="topology-tree-view-overflow">
    <v-treeview
      v-if="topologies.length"
      :items="topologies"
      dense
      hoverable
      activatable
      open-on-click
      return-object
      :active.sync="active"
      :open.sync="opened"
      class="mt-3"
      @update:active="onActive"
    >
      <template #prepend="{ item, open }">
        <v-icon v-if="TOPOLOGY_TREE.FOLDER === item.type" color="primary">
          {{ open ? 'mdi-folder-outline' : 'mdi-folder' }}
        </v-icon>
        <v-icon v-else :color="topologyColor(item)"> account_tree </v-icon>
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
            <span>{{ topologyTooltip(item) }}</span>
          </template>
        </tooltip>
      </template>
      <template #append="{ item }">
        <topology-tree-view-menu
          v-if="TOPOLOGY_TREE.TOPOLOGY === item.type"
          :topologies="topologies"
          :topology="item"
        />
        <folder-menu v-else in :topologies="topologies" :topology="item" />
      </template>
    </v-treeview>
    <div v-else class="text-center">
      <span> {{ $t('sidebar.noTopologiesFound') }} </span>
    </div>
  </div>
</template>

<script>
import { TOPOLOGY_TREE } from '@/services/enums/topologyTreeEnums'
import { TOPOLOGIES } from '@/store/modules/topologies/types'
import { mapActions, mapGetters, mapState } from 'vuex'
import { REQUESTS_STATE } from '@/store/modules/api/types'
import { API } from '@/api'
import ProgressBarCircular from '../../../commons/progressIndicators/ProgressBarCircular'
import FolderMenu from '../../folder/menu/FolderMenu'
import { TOPOLOGY_ENUMS } from '@/services/enums/topologyEnums'
import { ROUTES } from '@/services/enums/routerEnums'
import TopologyTreeViewMenu from '@/components/app/topology/menu/TopologyTreeViewMenu'
import Tooltip from '@/components/commons/tooltip/Tooltip'

export default {
  name: 'TopologyTreeView',
  components: { Tooltip, TopologyTreeViewMenu, ProgressBarCircular, FolderMenu },
  data() {
    return {
      ROUTES,
      PAGE_TABS_ENUMS: TOPOLOGY_ENUMS,
      TOPOLOGY_TREE,
      opened: JSON.parse(localStorage.getItem('treeView')) || [],
      active: [],
      lastOpened: {},
    }
  },
  computed: {
    ...mapState(TOPOLOGIES.NAMESPACE, ['topologies']),
    ...mapState(TOPOLOGIES.NAMESPACE, ['topology']),
    ...mapGetters(REQUESTS_STATE.NAMESPACE, [REQUESTS_STATE.GETTERS.GET_STATE]),
    state() {
      return this[REQUESTS_STATE.GETTERS.GET_STATE]([API.topology.getList.id, API.folder.getList.id])
    },
  },
  methods: {
    ...mapActions(TOPOLOGIES.NAMESPACE, [
      TOPOLOGIES.ACTIONS.DATA.GET_TOPOLOGIES,
      TOPOLOGIES.ACTIONS.TOPOLOGY.GET_BY_ID,
    ]),
    topologyTooltip(item) {
      if (item.type === 'CATEGORY') {
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
      return TOPOLOGY_TREE.TOPOLOGY === item.type ? `v.${item.version}` : ''
    },
    async onActive(activeItems) {
      if (!activeItems[0]) {
        return
      } else {
        this.lastOpened = activeItems[0]
      }
      if (this.$route.params.id === activeItems[0].id) {
        return
      }
      if (activeItems[0].type === TOPOLOGY_TREE.FOLDER) {
        return
      }
      await this[TOPOLOGIES.ACTIONS.TOPOLOGY.GET_BY_ID]({ id: activeItems[0].id })
      await this.$router.push({ name: ROUTES.TOPOLOGY.VIEWER, params: { id: activeItems[0].id } })
    },
  },
  watch: {
    $route(route) {
      if (!route.matched.some((route) => route.name === ROUTES.TOPOLOGY.DEFAULT)) {
        this.lastOpened = null
        this.active = []
      } else {
        if (route.params.isNewTopology) {
          this.lastOpened = []
          this.active = [{ id: route.params.id }]
        }
      }
    },
    active(activeItem) {
      if (activeItem.length === 0) {
        if (this.lastOpened?._id) {
          this.active = [this.lastOpened]
        }
      }
    },
    opened(treeView) {
      localStorage.setItem('treeView', JSON.stringify(treeView ? treeView : []))
    },
  },
  async created() {
    await this[TOPOLOGIES.ACTIONS.DATA.GET_TOPOLOGIES]()
    if (this.$route.params.id) {
      this.active = [{ id: this.$route.params.id }]
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