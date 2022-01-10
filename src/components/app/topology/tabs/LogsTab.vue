<template>
  <data-grid
    :headers="headers"
    :is-loading="state.isSending"
    :namespace="DATA_GRIDS.TOPOLOGY_LOGS"
    expand-click
    :quick-filters="quickFilters"
    :title="$t('topologies.logs.title')"
    show-expand
    disable-search
    show-full-text-search
    disabled-advanced-filter
    :permanent-filter="[[{ column: 'topology_id', operator: 'EQUAL', value: [''], default: true }]]"
    :request-params="{ topologyID: $route.params.id }"
  >
    <template #expand="{ items }">
      <span class="body-2">{{ items.item.message }}</span>
    </template>
    <template #default="{ items, isVisible, expanded }">
      <td v-if="isVisible('timestamp')" :style="expanded ? 'border-bottom: none' : ''" class="py-0 text-start">
        {{ items.item.timestamp | internationalFormat }}
      </td>
      <tooltip>
        <template #activator="{ on, attrs }">
          <td
            v-if="isVisible('correlation_id')"
            v-bind="attrs"
            :style="expanded ? 'border-bottom: none' : ''"
            :class="items.item.correlation_id ? 'pr-9' : ''"
            class="py-0 text-center truncate td-relative-container"
            v-on="on"
          >
            <v-btn v-if="items.item.correlation_id" class="button-absolute" icon @click.stop="copyToClipboard">
              <v-icon> mdi-content-copy </v-icon>
            </v-btn>
            {{ items.item.correlation_id ? items.item.correlation_id : 'system log - no id' }}
          </td>
        </template>
        <template #tooltip>
          <span> {{ items.item.correlation_id ? items.item.correlation_id : 'system log - no id' }}</span>
        </template>
      </tooltip>
      <td v-if="isVisible('node_id')" :style="expanded ? 'border-bottom: none' : ''" class="py-0 text-center truncate">
        {{ items.item.node_id }}
      </td>
      <td v-if="isVisible('node_name')" :style="expanded ? 'border-bottom: none' : ''" class="py-0 text-center">
        {{ items.item.node_name }}
      </td>
      <td v-if="isVisible('message')" :style="expanded ? 'border-bottom: none;' : ''" class="py-0 text-start truncate">
        <span>{{ items.item.message }}</span>
      </td>
    </template>
  </data-grid>
</template>

<script>
import { internationalFormat } from '@/services/utils/dateFilters'
import { DATA_GRIDS } from '@/services/enums/dataGridEnums'
import DataGrid from '@/components/commons/table/DataGrid'
import { REQUESTS_STATE } from '@/store/modules/api/types'
import { API } from '@/api'
import { mapGetters } from 'vuex'
import Tooltip from '@/components/commons/tooltip/Tooltip'
import FlashMessageMixin from '@/components/commons/mixins/FlashMessageMixin'
import QuickFiltersMixin from '@/components/commons/mixins/QuickFiltersMixin'
export default {
  name: 'LogsTab',
  components: { Tooltip, DataGrid },
  mixins: [FlashMessageMixin, QuickFiltersMixin],
  computed: {
    ...mapGetters(REQUESTS_STATE.NAMESPACE, [REQUESTS_STATE.GETTERS.GET_STATE]),
    state() {
      return this[REQUESTS_STATE.GETTERS.GET_STATE](API.topology.getLogs.id)
    },
  },
  data() {
    return {
      DATA_GRIDS,
      search: null,
      headers: [
        {
          text: 'topologies.logs.headers.timestamp',
          value: 'timestamp',
          align: 'left',
          sortable: true,
          visible: true,
          width: '10%',
        },
        {
          text: 'topologies.logs.headers.correlation_id',
          value: 'correlation_id',
          align: 'center',
          sortable: true,
          visible: true,
          width: '15%',
        },
        {
          text: 'topologies.logs.headers.nodeID',
          value: 'node_id',
          align: 'center',
          sortable: true,
          visible: true,
          width: '20%',
        },
        {
          text: 'topologies.logs.headers.nodeName',
          value: 'node_name',
          align: 'center',
          sortable: true,
          visible: true,
          width: '15%',
        },
        {
          text: 'topologies.logs.headers.message',
          value: 'message',
          align: 'center',
          sortable: true,
          visible: true,
          width: '40%',
        },
      ],
    }
  },
  methods: {
    copyToClipboard(e) {
      navigator.clipboard.writeText(e.target.innerHTML.trim())
      this.showFlashMessage(false, 'ID copied!')
    },
  },
  created() {
    if (this.$route.params.id) {
      this.search = this.$route.params.id
    }
  },
  filters: {
    internationalFormat,
  },
  mounted() {
    this.init('timestamp')
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
tr:hover {
  cursor: pointer;
  //& + .expanded-row {
  //  background-color: #eeeeee !important;
  //}
}
.transform-center-cell {
  margin-left: 15%;
}
</style>
