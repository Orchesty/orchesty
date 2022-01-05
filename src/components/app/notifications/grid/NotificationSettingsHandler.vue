<template>
  <v-row>
    <v-col cols="12" md="6">
      <notification-service-controls />
    </v-col>
    <v-col cols="12" md="6">
      <notification-event-controls />
    </v-col>
  </v-row>
</template>

<script>
import NotificationEventControls from './NotificationEventControls'
import NotificationServiceControls from './NotificationServiceControls'
export default {
  name: 'NotificationSettingsHandler',
  components: { NotificationServiceControls, NotificationEventControls },
}
</script>
