<template>
  <content-basic :title="$t('navigation.notifications')">
    <notification-settings />
  </content-basic>
</template>

<script>
import NotificationSettings from '../../components/app/notifications/grid/NotificationSettings'
import ContentBasic from '@/components/layout/content/ContentBasic'

export default {
  name: 'NotificationPage',
  components: { ContentBasic, NotificationSettings },
}
</script>
