<template>
  <data-grid
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
import DataGrid from '@/components/commons/table/DataGrid'
import { DATA_GRIDS } from '@/services/enums/dataGridEnums'

export default {
  name: 'HealthCheckQueue',
  components: { DataGrid },
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
          align: 'center',
          sortable: true,
          visible: true,
          width: '50%',
        },
      ],
    }
  },
}
</script>

<style scoped></style>
