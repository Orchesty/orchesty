<template>
  <data-grid
    :headers="headers"
    :is-loading="state.isSending"
    :namespace="DATA_GRIDS.NOTIFICATIONS"
    disable-filter
    disable-pagination
    disable-search
  >
    <template #default="{ items }">
      <td>{{ items.item.name }}</td>
      <td class="text-center">
        <template v-if="items.item.status">{{ $t('notifications.serviceOk') }}</template>
        <template v-else>{{ $t('notifications.serviceNOk') }}</template>
      </td>
      <td class="text-center">
        <notification-update-modal :service="getService(items.item.name)" />
      </td>
    </template>
  </data-grid>
</template>

<script>
import DataGrid from '../../../commons/table/DataGrid'
import { DATA_GRIDS } from '@/services/enums/dataGridEnums'
import { mapGetters, mapState } from 'vuex'
import { REQUESTS_STATE } from '../../../../store/modules/api/types'
import { API } from '../../../../api'
import NotificationUpdateModal from '../modal/NotificationUpdateModal'
export default {
  name: 'NotificationServiceControls',
  components: { NotificationUpdateModal, DataGrid },
  computed: {
    ...mapGetters(REQUESTS_STATE.NAMESPACE, [REQUESTS_STATE.GETTERS.GET_STATE]),
    ...mapState(DATA_GRIDS.NOTIFICATIONS, ['items']),
    state() {
      return this[REQUESTS_STATE.GETTERS.GET_STATE]([API.notification.events.id, API.notification.grid.id])
    },
  },
  methods: {
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
          align: 'center',
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
}
</script>
