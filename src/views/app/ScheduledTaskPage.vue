<template>
  <page-layout :title="$t('navigation.scheduledTask')">
    <scheduled-task-grid-handler />
  </page-layout>
</template>

<script>
import PageLayout from '../../components/layout/PageLayout'
import ScheduledTaskGridHandler from '../../components/app/scheduledTask/grid/ScheduledTaskGridHandler'

export default {
  name: 'ScheduledTaskPage',
  components: { ScheduledTaskGridHandler, PageLayout },
}
</script>
