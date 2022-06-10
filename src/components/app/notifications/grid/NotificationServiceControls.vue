<template>
  <data-grid
    ref="grid"
    :headers="headers"
    :is-loading="state.isSending"
    :namespace="DATA_GRIDS.NOTIFICATIONS"
    disable-filter
    disable-pagination
    disable-search
  >
    <template #default="{ items }">
      <td>{{ items.item.name }}</td>
      <td class="text-start">
        <template v-if="items.item.status">{{ $t('notifications.serviceOk') }}</template>
        <template v-else-if="!hasServiceSettings(items.item.settings)">Service not set</template>
        <template v-else>{{ $t('notifications.serviceNOk') }}</template>
      </td>
      <td class="text-center">
        <notification-update-modal :service="items.item" />
      </td>
    </template>
  </data-grid>
</template>

<script>
import DataGrid from '../../../commons/grid/DataGrid'
import { DATA_GRIDS } from '@/services/enums/dataGridEnums'
import { mapGetters } from 'vuex'
import { REQUESTS_STATE } from '../../../../store/modules/api/types'
import { API } from '../../../../api'
import NotificationUpdateModal from '../modal/NotificationUpdateModal'
import { GRID } from '@/store/modules/grid/types'
export default {
  name: 'NotificationServiceControls',
  components: { NotificationUpdateModal, DataGrid },
  computed: {
    ...mapGetters(DATA_GRIDS.SCHEDULED_TASK, {
      sorterInitial: GRID.GETTERS.GET_SORTER,
      pagingInitial: GRID.GETTERS.GET_PAGING,
    }),
    ...mapGetters(REQUESTS_STATE.NAMESPACE, [REQUESTS_STATE.GETTERS.GET_STATE]),
    state() {
      return this[REQUESTS_STATE.GETTERS.GET_STATE]([API.notification.events.id, API.notification.grid.id])
    },
  },
  methods: {
    hasServiceSettings(service) {
      return Object.keys(service).length
    },
    getService(name) {
      if (name) {
        let service
        this.items.forEach((notification) => {
          if (notification.name === name) {
            service = notification
          }
        })
        return service
      }
    },
  },
  data() {
    return {
      DATA_GRIDS,
      headers: [
        {
          text: 'notifications.service',
          value: 'title',
          align: 'left',
          sortable: false,
          visible: true,
          width: '100px',
        },
        {
          text: 'notifications.set',
          value: 'title',
          align: 'left',
          sortable: false,
          visible: true,
          width: '200px',
        },
        {
          text: 'notifications.edit',
          value: 'title',
          align: 'center',
          sortable: false,
          visible: true,
          width: '100px',
        },
      ],
    }
  },
  async mounted() {
    await this.$refs.grid.fetchGridWithInitials(null, null, null, this.pagingInitial, this.sorterInitial)
  },
}
</script>
