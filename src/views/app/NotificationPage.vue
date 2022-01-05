<template>
  <page-layout :title="$t('navigation.notifications')">
    <notification-settings-handler />
  </page-layout>
</template>

<script>
import PageLayout from '../../components/layout/PageLayout'
import NotificationSettingsHandler from '../../components/app/notifications/grid/NotificationSettingsHandler'

export default {
  name: 'NotificationPage',
  components: { NotificationSettingsHandler, PageLayout },
}
</script>
