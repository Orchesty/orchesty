<template>
  <data-grid
    :headers="headers"
    :is-loading="state.isSending"
    :namespace="DATA_GRIDS.EVENTS"
    :fixed-items="events"
    disable-filter
    disable-search
    disable-pagination
  >
    <template #default="{ items }">
      <td>{{ items.item.title }}</td>
      <td v-for="(notification, key) in notifications" :key="key" class="text-center">
        <tooltip v-if="updating.isSending || notification.settings.length === 0">
          <template #activator="{ on, attrs }">
            <div v-bind="attrs" v-on="on">
              <v-switch
                :disabled="updating.isSending || notification.settings.length === 0"
                :loading="selectedKey[0] === items.item.key && selectedKey[1] === key && updating.errors.length === 0"
                hide-details
                :input-value="eventEnabled(notification, items.item.key)"
                class="notification-switch"
                @change="updated(notification, items.item.key, key)"
              />
            </div>
          </template>
          <template #tooltip>
            {{ updating.isSending ? 'Service is being updated' : 'Set the service first' }}
          </template>
        </tooltip>
        <v-switch
          v-else
          :disabled="updating.isSending || notification.settings.length === 0"
          :loading="selectedKey[0] === items.item.key && selectedKey[1] === key && updating.errors.length === 0"
          hide-details
          :input-value="eventEnabled(notification, items.item.key)"
          class="notification-switch"
          @change="updated(notification, items.item.key, key)"
        />
      </td>
    </template>
  </data-grid>
</template>

<script>
import DataGrid from '../../../commons/grid/DataGrid'
import { NOTIFICATIONS } from '../../../../store/modules/notifications/types'
import { mapActions, mapGetters } from 'vuex'
import { REQUESTS_STATE } from '../../../../store/modules/api/types'
import { API } from '../../../../api'
import { DATA_GRIDS } from '@/services/enums/dataGridEnums'
import Tooltip from '@/components/commons/Tooltip'

export default {
  name: 'NotificationEventControls',
  data() {
    return {
      DATA_GRIDS,
      selectedKey: [null, null],
      headers: [
        {
          text: 'notifications.event',
          value: 'title',
          align: 'left',
          sortable: false,
          visible: true,
          width: '200px',
        },
      ],
    }
  },
  components: { Tooltip, DataGrid },
  computed: {
    ...mapGetters(REQUESTS_STATE.NAMESPACE, [REQUESTS_STATE.GETTERS.GET_STATE]),
    ...mapGetters(NOTIFICATIONS.NAMESPACE, {
      notifications: NOTIFICATIONS.GETTERS.GET_NOTIFICATIONS,
      events: NOTIFICATIONS.GETTERS.GET_EVENTS,
    }),
    state() {
      return this[REQUESTS_STATE.GETTERS.GET_STATE]([API.notification.events.id, API.notification.grid.id])
    },
    updating() {
      return this[REQUESTS_STATE.GETTERS.GET_STATE]([API.notification.update.id])
    },
  },
  methods: {
    ...mapActions(NOTIFICATIONS.NAMESPACE, [
      NOTIFICATIONS.ACTIONS.GET_NOTIFICATION_LIST_REQUEST,
      NOTIFICATIONS.ACTIONS.UPDATE_NOTIFICATIONS_REQUEST,
      NOTIFICATIONS.ACTIONS.GET_NOTIFICATION_EVENTS_REQUEST,
    ]),
    eventEnabled(notification, key) {
      if (notification.events.length > 0 && key) {
        return notification.events.includes(key)
      }
      return false
    },
    async updated(notification, key, index) {
      this.selectedKey = [key, index]
      if (notification.events.includes(key)) {
        let index = notification.events.indexOf(key)
        notification.events.splice(index, 1)
        await this[NOTIFICATIONS.ACTIONS.UPDATE_NOTIFICATIONS_REQUEST]({
          events: notification.events,
          settings: notification,
          id: notification.id,
        })
      } else {
        await this[NOTIFICATIONS.ACTIONS.UPDATE_NOTIFICATIONS_REQUEST]({
          id: notification.id,
          events: notification.events.push(key),
          settings: notification,
        })
      }
      this.selectedKey = [null, null]
    },
  },
  async created() {
    await this[NOTIFICATIONS.ACTIONS.GET_NOTIFICATION_EVENTS_REQUEST]()
    await this[NOTIFICATIONS.ACTIONS.GET_NOTIFICATION_LIST_REQUEST]()

    this.notifications.forEach((notification) => {
      this.headers.push({
        text: `notifications.${notification.type}`,
        value: notification.type,
        align: 'left',
        sortable: false,
        visible: true,
        width: '100px',
      })
    })
  },
}
</script>
<style lang="scss" scoped>
.notification-switch::v-deep {
  margin: 0;
  padding: 0;
  .v-input__slot {
    display: flex;
    justify-content: center;
  }
}
</style>
