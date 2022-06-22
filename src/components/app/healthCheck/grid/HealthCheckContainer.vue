<template>
  <data-grid
    ref="grid"
    :headers="headers"
    :is-loading="false"
    :namespace="DATA_GRIDS.HEALTH_CHECK_CONTAINERS"
    disabled-advanced-filter
    disable-pagination
  >
    <template #default="{ items, isVisible }">
      <td v-if="isVisible('container')">
        {{ items.item.name }}
      </td>
      <td v-if="isVisible('status')">
        {{ items.item.up ? 'Healthy' : 'Unhealthy' }}
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
  name: 'HealthCheckContainer',
  components: { DataGrid },
  computed: {
    ...mapGetters(DATA_GRIDS.HEALTH_CHECK_CONTAINERS, {
      sorterInitial: GRID.GETTERS.GET_SORTER,
      pagingInitial: GRID.GETTERS.GET_PAGING,
    }),
  },
  data() {
    return {
      DATA_GRIDS,
      headers: [
        {
          text: 'topologies.healthCheck.headers.container.name',
          value: 'container',
          align: 'left',
          sortable: false,
          visible: true,
          width: '50%',
        },
        {
          text: 'topologies.healthCheck.headers.container.status',
          value: 'status',
          align: 'left',
          sortable: false,
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
