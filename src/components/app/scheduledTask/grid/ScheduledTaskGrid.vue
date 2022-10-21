<template>
  <data-grid
    ref="grid"
    :headers="headers"
    :is-loading="state.isSending"
    :namespace="DATA_GRIDS.SCHEDULED_TASK"
    disable-filter
    disable-pagination
    return-row-props
    @row-props="redirect"
  >
    <template #default="{ items, isVisible }">
      <td v-if="isVisible('topology')" @click="redirect(items)">
        {{ `${items.item.topology.name}.v${items.item.topology.version}` }}
      </td>
      <td v-if="isVisible('node')">
        {{ items.item.node.name }}
      </td>
      <td v-if="isVisible('time')">
        {{ items.item.time }}
      </td>
      <td
        v-if="isVisible('time')"
        :key="now.getMilliseconds()"
        :class="isEnabled(items.item) ? '' : 'grey--text darken-1--text'"
      >
        {{ $options.filters.internationalFormat(timeParser(items.item.time)) }}
      </td>
      <td
        v-if="isVisible('status')"
        :class="isEnabled(items.item) ? 'task-active' : 'task-disabled'"
        class="font-weight-bold text-uppercase"
        :title="isEnabled(items.item)"
      >
        <span v-if="isEnabled(items.item)"> active </span>
        <span v-else> disabled </span>
      </td>
    </template>
  </data-grid>
</template>
<script>
import { DATA_GRIDS } from '@/services/enums/dataGridEnums'
import DataGrid from '../../../commons/grid/DataGrid'
import { REQUESTS_STATE } from '../../../../store/modules/api/types'
import { API } from '../../../../api'
import { mapActions, mapGetters } from 'vuex'
import { ROUTES } from '@/services/enums/routerEnums'
import cronParser from 'cron-parser'
import { internationalFormat } from '@/services/utils/dateFilters'
import { GRID } from '@/store/modules/grid/types'
import { TOPOLOGIES } from '@/store/modules/topologies/types'

export default {
  name: 'ScheduledTaskGrid',
  components: { DataGrid },
  computed: {
    ...mapGetters(DATA_GRIDS.SCHEDULED_TASK, {
      sorterInitial: GRID.GETTERS.GET_SORTER,
      pagingInitial: GRID.GETTERS.GET_PAGING,
    }),
    ...mapGetters(REQUESTS_STATE.NAMESPACE, [REQUESTS_STATE.GETTERS.GET_STATE]),
    state() {
      return this[REQUESTS_STATE.GETTERS.GET_STATE]([API.scheduledTask.grid.id])
    },
  },
  created() {
    this.timer = setInterval(this.refreshTime, 60000) // run every minute
  },
  methods: {
    ...mapActions(TOPOLOGIES.NAMESPACE, [TOPOLOGIES.ACTIONS.TOPOLOGY.GET_BY_ID]),
    isEnabled(item) {
      if (item) {
        return item.topology.status
      }
    },
    async redirect({ item }) {
      await this[TOPOLOGIES.ACTIONS.TOPOLOGY.GET_BY_ID](item.topology.id)
      this.$router.push({ name: ROUTES.TOPOLOGY.VIEWER, params: { id: item.topology.id } })
    },
    timeParser(time) {
      let interval = this.cronParser.parseExpression(time)
      interval = interval.next().toString().slice(0, 24)
      return interval
    },
    refreshTime() {
      this.now = new Date()
    },
  },
  beforeDestroy() {
    clearInterval(this.timer)
  },
  data() {
    return {
      cronParser,
      DATA_GRIDS,
      renderContent: true,
      timer: null,
      now: new Date(),
      headers: [
        {
          text: this.$t('grid.header.topology'),
          value: 'topology',
          align: 'left',
          sortable: true,
          visible: true,
          width: '360px',
        },
        {
          text: this.$t('grid.header.node'),
          value: 'node',
          align: 'left',
          sortable: true,
          visible: true,
          width: '200px',
        },
        {
          text: this.$t('grid.header.settings'),
          value: 'time',
          align: 'left',
          sortable: true,
          visible: true,
          width: '120px',
        },
        {
          text: this.$t('grid.header.nextRun'),
          value: 'time',
          align: 'left',
          sortable: true,
          visible: true,
          width: '161px',
        },
        {
          text: this.$t('grid.header.status'),
          value: 'status',
          align: 'left',
          sortable: true,
          visible: true,
          width: '150px',
        },
      ],
    }
  },
  filters: {
    internationalFormat,
  },
  async mounted() {
    await this.$refs.grid.fetchGridWithInitials(null, null, null, this.pagingInitial, this.sorterInitial)
  },
}
</script>

<style scoped>
.task-active {
  color: var(--v-success-base) !important;
}
.task-disabled {
  color: var(--v-error-base) !important;
}
</style>
