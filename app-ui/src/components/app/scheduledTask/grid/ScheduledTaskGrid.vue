<template>
  <data-grid
    :headers="headers"
    :is-loading="state.isSending"
    :namespace="DATA_GRIDS.SCHEDULED_TASK"
    disable-filter
    disable-pagination
    disable-search
    return-row-props
    @row-props="redirect"
  >
    <template #default="{ items, isVisible }">
      <td v-if="isVisible('topology')">
        {{ `${items.item.topology.name}.v${items.item.topology.version}` }}
      </td>
      <td v-if="isVisible('node')">
        {{ items.item.node.name }}
      </td>
      <td v-if="isVisible('time')">
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
import DataGrid from '../../../commons/table/DataGrid'
import { REQUESTS_STATE } from '../../../../store/modules/api/types'
import { API } from '../../../../api'
import { mapGetters } from 'vuex'
import { ROUTES } from '@/services/enums/routerEnums'
import cronParser from 'cron-parser'
import { internationalFormat } from '@/services/utils/dateFilters'

export default {
  name: 'ScheduledTaskGrid',
  components: { DataGrid },
  computed: {
    ...mapGetters(REQUESTS_STATE.NAMESPACE, [REQUESTS_STATE.GETTERS.GET_STATE]),
    state() {
      return this[REQUESTS_STATE.GETTERS.GET_STATE]([API.scheduledTask.grid.id])
    },
  },
  methods: {
    isEnabled(item) {
      if (item) {
        return item.topology.status
      }
    },
    redirect({ item }) {
      this.$router.push({ name: ROUTES.TOPOLOGY.VIEWER, params: { id: item.node.name } })
    },
    timeParser(time) {
      let interval = this.cronParser.parseExpression(time)
      interval = interval.next().toString().slice(0, 24)
      return interval
    },
  },
  data() {
    return {
      cronParser,
      DATA_GRIDS,
      headers: [
        {
          text: 'scheduledTask.grid.topology',
          value: 'topology',
          align: 'left',
          sortable: true,
          visible: true,
          width: '360px',
        },
        {
          text: 'scheduledTask.grid.node',
          value: 'node',
          align: 'center',
          sortable: true,
          visible: true,
          width: '200px',
        },
        {
          text: 'scheduledTask.grid.settings',
          value: 'time',
          align: 'center',
          sortable: true,
          visible: true,
          width: '161px',
        },
        {
          text: 'scheduledTask.grid.status',
          value: 'status',
          align: 'center',
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
