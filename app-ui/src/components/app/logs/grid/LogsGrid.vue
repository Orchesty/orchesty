<template>
  <data-grid
    ref="grid"
    :headers="headers"
    :is-loading="state.isSending"
    :namespace="DATA_GRIDS.LOGS"
    expand-click
    :quick-filters="quickFilters"
    show-expand
    simple-filter
    :simple-filter-enum="SIMPLE_FILTER.LOGS"
  >
    <template #expand="{ items }">
      <span>{{ items.item.message }}</span>
    </template>
    <template #default="{ items, isVisible, expanded }">
      <td v-if="isVisible('timestamp')" :style="expanded ? 'border-bottom: none' : ''">
        {{ items.item.timestamp | internationalFormat }}
      </td>
      <td v-if="isVisible('topology_name')" :style="expanded ? 'border-bottom: none' : ''">
        {{ items.item.topology_name }}
      </td>
      <td v-if="isVisible('node_id')" :style="expanded ? 'border-bottom: none' : ''">
        {{ items.item.node_id }}
      </td>
      <td v-if="isVisible('node_name')" :style="expanded ? 'border-bottom: none' : ''">
        {{ items.item.node_name }}
      </td>
      <td v-if="isVisible('severity')" :style="expanded ? 'border-bottom: none' : ''">
        <span :class="`font-weight-bold ${setColor(items.item.severity)}--text text-uppercase`">{{
          items.item.severity
        }}</span>
      </td>
      <tooltip>
        <template #activator="{ on, attrs }">
          <td
            v-if="isVisible('correlation_id')"
            v-bind="attrs"
            :style="expanded ? 'border-bottom: none' : ''"
            class="text-end"
            v-on="on"
          >
            <v-btn v-if="items.item.correlation_id" icon @click.stop="copyToClipboard(items.item.correlation_id)">
              <app-icon>mdi-content-copy</app-icon>
            </v-btn>
          </td>
        </template>
        <template #tooltip>
          {{ items.item.correlation_id ? items.item.correlation_id : 'system log - no id' }}
        </template>
      </tooltip>
    </template>
  </data-grid>
</template>

<script>
import { internationalFormat } from '@/services/utils/dateFilters'
import { DATA_GRIDS } from '@/services/enums/dataGridEnums'
import DataGrid from '@/components/commons/grid/DataGrid'
import { REQUESTS_STATE } from '@/store/modules/api/types'
import { API } from '@/api'
import { mapGetters } from 'vuex'
import Tooltip from '@/components/commons/Tooltip'
import FlashMessageMixin from '@/services/mixins/FlashMessageMixin'
import QuickFiltersMixin from '@/services/mixins/QuickFiltersMixin'
import { SIMPLE_FILTER } from '@/services/enums/dataGridFilterEnums'
import AppIcon from '@/components/commons/icon/AppIcon'
import { GRID } from '@/store/modules/grid/types'
export default {
  name: 'LogsGrid',
  components: { AppIcon, Tooltip, DataGrid },
  mixins: [FlashMessageMixin, QuickFiltersMixin],
  computed: {
    ...mapGetters(DATA_GRIDS.LOGS, {
      sorterInitial: GRID.GETTERS.GET_SORTER,
      pagingInitial: GRID.GETTERS.GET_PAGING,
    }),
    ...mapGetters(REQUESTS_STATE.NAMESPACE, [REQUESTS_STATE.GETTERS.GET_STATE]),
    state() {
      return this[REQUESTS_STATE.GETTERS.GET_STATE](API.topology.getLogs.id)
    },
  },
  data() {
    return {
      SIMPLE_FILTER,
      DATA_GRIDS,
      search: '',
      headers: [
        {
          text: 'topologies.logs.headers.timestamp',
          value: 'timestamp',
          align: 'left',
          sortable: true,
          visible: true,
          width: '20%',
        },
        {
          text: 'topologies.logs.headers.topologyName',
          value: 'topology_name',
          align: 'left',
          sortable: true,
          visible: true,
          width: '20%',
        },
        {
          text: 'topologies.logs.headers.nodeID',
          value: 'node_id',
          align: 'left',
          sortable: true,
          visible: true,
          width: '20%',
        },
        {
          text: 'topologies.logs.headers.nodeName',
          value: 'node_name',
          align: 'left',
          sortable: true,
          visible: true,
          width: '15%',
        },
        {
          text: 'topologies.logs.headers.severity',
          value: 'severity',
          align: 'left',
          sortable: true,
          visible: true,
          width: '10%',
        },
        {
          text: 'topologies.logs.headers.correlation_id',
          value: 'correlation_id',
          align: 'right',
          sortable: true,
          visible: true,
          width: '15%',
        },
      ],
    }
  },
  methods: {
    setColor(props) {
      if (props.toLowerCase() === 'error') {
        return 'error'
      }
      if (props.toLowerCase() === 'warning') {
        return 'warning'
      }
      if (props.toLowerCase() === 'ok') {
        return 'info'
      }
      return 'black'
    },
    copyToClipboard(correlationId) {
      navigator.clipboard.writeText(correlationId)
      this.showFlashMessage(false, 'ID copied!')
    },
  },
  async mounted() {
    this.init('timestamp')
    await this.$refs.grid.fetchGridWithInitials(null, null, null, this.pagingInitial, this.sorterInitial)
  },
  filters: {
    internationalFormat,
  },
}
</script>
<style lang="scss">
.expanded-row {
  border-bottom: 1px solid #e0e0e0 !important;
  cursor: pointer;
  &:hover {
    background-color: #eeeeee !important;
  }
}
.truncate-message {
  overflow: hidden;
  text-overflow: ellipsis;
  display: -webkit-box;
  -webkit-line-clamp: 1;
  -webkit-box-orient: vertical;
}
tr:hover {
  cursor: pointer;
}
.transform-center-cell {
  margin-left: 15%;
}
</style>
