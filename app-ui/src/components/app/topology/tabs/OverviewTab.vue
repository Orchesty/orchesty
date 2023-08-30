<template>
  <div v-if="topologyID">
    <data-grid
      ref="grid"
      :is-loading="state.isSending"
      :namespace="DATA_GRIDS.OVERVIEW"
      :quick-filters="quickFilters"
      :headers="headers"
      :request-params="{ id: topologyID }"
      stats
      disable-search
      disabled-advanced-filter
    >
      <template #default="{ items, isVisible }">
        <td v-if="isVisible('started')" class="py-0">
          {{ items.item.started | internationalFormat }}
        </td>
        <td v-if="isVisible('finished')" class="py-0 text-center">
          {{ filterDate(items.item.finished) }}
        </td>
        <tooltip>
          <template #activator="{ on, attrs }">
            <td
              v-if="isVisible('correlation_id')"
              v-bind="attrs"
              :class="items.item.correlationId ? 'pr-9' : ''"
              class="py-0 text-center truncate td-relative-container"
              v-on="on"
            >
              {{ items.item.correlationId ? items.item.correlationId : 'system log - no id' }}
              <v-btn
                v-if="items.item.correlationId"
                class="button-absolute"
                icon
                @click.stop="copyToClipboard(items.item.correlationId)"
              >
                <v-icon> mdi-content-copy </v-icon>
              </v-btn>
            </td>
          </template>
          <template #tooltip>
            <span> {{ items.item.correlationId ? items.item.correlationId : 'system log - no id' }}</span>
          </template>
        </tooltip>
        <td v-if="isVisible('duration')" class="py-0 text-center">
          {{ prettyMs(items.item.duration, { keepDecimalsOnWholeSeconds: true }) }}
        </td>
        <td v-if="isVisible('progress')" class="py-0">
          {{ items.item.nodesProcessed + '/' + items.item.nodesTotal }}
        </td>
        <td v-if="isVisible('status')" class="py-0 font-weight-bold">
          <div class="d-flex align-center justify-center">
            <span :class="`text-uppercase ${setColor(items.item.status)}--text`">
              {{ items.item.status }}
            </span>
          </div>
        </td>
      </template>
    </data-grid>
  </div>
</template>

<script>
import { internationalFormat } from '@/services/utils/dateFilters'
import { DATA_GRIDS } from '@/services/enums/dataGridEnums'
import DataGrid from '@/components/commons/table/DataGrid'
import { REQUESTS_STATE } from '@/store/modules/api/types'
import { API } from '@/api'
import { mapActions, mapGetters, mapState } from 'vuex'
import { TOPOLOGIES } from '@/store/modules/topologies/types'
import { GRID } from '@/store/modules/grid/types'
import prettyMilliseconds from 'pretty-ms'
import Tooltip from '@/components/commons/tooltip/Tooltip'
import FlashMessageMixin from '@/components/commons/mixins/FlashMessageMixin'
import QuickFiltersMixin from '@/components/commons/mixins/QuickFiltersMixin'

export default {
  name: 'OverviewTab',
  components: { Tooltip, DataGrid },
  mixins: [FlashMessageMixin, QuickFiltersMixin],
  computed: {
    ...mapGetters(REQUESTS_STATE.NAMESPACE, [REQUESTS_STATE.GETTERS.GET_STATE]),
    state() {
      return this[REQUESTS_STATE.GETTERS.GET_STATE](API.overview.grid.id)
    },
    ...mapState(TOPOLOGIES.NAMESPACE, ['topology']),
  },
  data() {
    return {
      DATA_GRIDS,
      topologyID: null,
      headers: [
        {
          text: 'topologies.overview.headers.created',
          value: 'started',
          align: 'left',
          sortable: true,
          visible: true,
          width: '15%',
        },
        {
          text: 'topologies.overview.headers.finished',
          value: 'finished',
          align: 'center',
          sortable: true,
          visible: true,
          width: '15%',
        },
        {
          text: 'topologies.overview.headers.correlation_id',
          value: 'correlation_id',
          align: 'center',
          sortable: true,
          visible: true,
          width: '15%',
        },
        {
          text: 'topologies.overview.headers.duration',
          value: 'duration',
          align: 'center',
          sortable: false,
          visible: true,
          width: '15%',
        },
        {
          text: 'topologies.overview.headers.progress',
          value: 'progress',
          align: 'center',
          sortable: false,
          visible: true,
          width: '15%',
        },
        {
          text: 'topologies.overview.headers.status',
          value: 'status',
          align: 'center',
          sortable: false,
          visible: true,
          width: '15%',
        },
      ],
    }
  },
  methods: {
    ...mapActions(DATA_GRIDS.EVENTS, [GRID.ACTIONS.GRID_FETCH]),
    prettyMs: prettyMilliseconds,
    filterDate(date) {
      if (date) {
        return this.$options.filters.internationalFormat(date)
      } else {
        return 'In progress..'
      }
    },
    copyToClipboard(correlationId) {
      navigator.clipboard.writeText(correlationId)
      this.showFlashMessage(false, 'ID copied!')
    },
    setColor(props) {
      if (props.toLowerCase() === 'failed') {
        return 'error'
      }
      if (props.toLowerCase() === 'in progress') {
        return 'black'
      }
      if (props.toLowerCase() === 'success') {
        return 'success'
      }
      return 'info'
    },
  },
  filters: {
    internationalFormat,
  },
  watch: {
    async topology(val) {
      if (this.topologyID !== val._id) {
        this.topologyID = val._id
        await this.$refs.grid.fetchGridWithParams({ id: val._id })
      }
    },
  },
  mounted() {
    if (this.topology) {
      this.topologyID = this.topology._id
    }
    this.init('started')
  },
}
</script>