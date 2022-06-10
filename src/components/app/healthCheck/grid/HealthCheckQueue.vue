<template>
  <data-grid
    ref="grid"
    :title="'Queue'"
    :headers="headers"
    :is-loading="false"
    :namespace="DATA_GRIDS.HEALTH_CHECK_QUEUES"
    disabled-advanced-filter
    disable-pagination
  >
    <template #default="{ items, isVisible }">
      <td v-if="isVisible('queue')">
        {{ items.item.queue }}
      </td>
      <td v-if="isVisible('consumers')">
        {{ items.item.consumers ? 'Healthy' : 'Unhealthy' }}
      </td>
    </template>
  </data-grid>
</template>

<script>
import DataGrid from '@/components/commons/grid/DataGrid'
import { DATA_GRIDS } from '@/services/enums/dataGridEnums'
import { mapGetters } from 'vuex'
import { GRID } from '@/store/modules/grid/types'

export default {
  name: 'HealthCheckQueue',
  components: { DataGrid },
  computed: {
    ...mapGetters(DATA_GRIDS.SCHEDULED_TASK, {
      sorterInitial: GRID.GETTERS.GET_SORTER,
      pagingInitial: GRID.GETTERS.GET_PAGING,
    }),
  },
  data() {
    return {
      DATA_GRIDS,
      headers: [
        {
          text: 'topologies.healthCheck.headers.queue',
          value: 'queue',
          align: 'left',
          sortable: true,
          visible: true,
          width: '50%',
        },
        {
          text: 'topologies.healthCheck.headers.consumers',
          value: 'consumers',
          align: 'left',
          sortable: true,
          visible: true,
          width: '50%',
        },
      ],
    }
  },
  async mounted() {
    await this.$refs.grid.fetchGridWithInitials(null, null, null, this.pagingInitial, this.sorterInitial)
  },
}
</script>

<style scoped></style>
