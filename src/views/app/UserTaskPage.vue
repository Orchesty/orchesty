<template>
  <page-layout :title="$t('navigation.userTask')">
    <user-task-grid-handler />
  </page-layout>
</template>

<script>
import PageLayout from '@/components/layout/PageLayout'
import UserTaskGridHandler from '@/components/app/userTasks/grid/UserTaskGridHandler'
export default {
  name: 'UserTasksPage',
  components: { PageLayout, UserTaskGridHandler },
}
</script>
