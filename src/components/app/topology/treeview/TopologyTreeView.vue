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
        <v-icon v-if="TOPOLOGY_TREE.FOLDER === item.type" color="secondary">
          {{ open ? 'mdi-folder-open' : 'mdi-folder' }}
        </v-icon>
        <v-icon v-else :color="topologyColor(item)"> account_tree </v-icon>
      </template>
      <template slot="label" slot-scope="{ item }">
        <tooltip>
          <template #activator="{ on, attrs }">
            <div class="d-flex subtitle-2" v-bind="attrs" v-on="on">
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
      <span class="body-1"> {{ $t('topologies.sidebar.noTopologiesFound') }} </span>
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
    async onActive(values) {
      if (!values.length) {
        await this.$router.push({ name: ROUTES.TOPOLOGY.OVERVIEW })
        return
      }
      if (values[0].type === TOPOLOGY_TREE.FOLDER) return
      if (this.$route.params.id === values[0].id) return
      await this.$router.push({ name: ROUTES.TOPOLOGY.VIEWER, params: { id: values[0].id } })
    },
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
      return item.visibility === TOPOLOGY_ENUMS.DRAFT ? 'gray' : item.enabled ? 'green' : 'red'
    },
    topologyTitleVersion(item) {
      return TOPOLOGY_TREE.TOPOLOGY === item.type ? `v.${item.version}` : ''
    },
  },
  async created() {
    await this[TOPOLOGIES.ACTIONS.DATA.GET_TOPOLOGIES]()
    if (this.$route.params.id) {
      this.active = [{ id: this.$route.params.id }]
    }
  },
  watch: {
    $route(val) {
      this.active = [{ id: val.params.id }]
    },
    opened(treeView) {
      localStorage.setItem('treeView', JSON.stringify(treeView ? treeView : []))
    },
  },
}
</script>

<style lang="scss" scoped>
.topology-tree-view-name {
  max-width: 210px;
  display: block;
}
.topology-tree-view-overflow {
  max-height: 85vh;
  overflow-y: auto;
}
</style>
