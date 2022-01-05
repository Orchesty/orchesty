<template>
  <content-basic :title="$t('navigation.userTask')">
    <user-task-grid />
  </content-basic>
</template>

<script>
import ContentBasic from '@/components/layout/content/ContentBasic'
import userTaskGrid from '@/components/app/userTasks/grid/UserTaskGrid'
export default {
  name: 'UserTasksPage',
  components: { ContentBasic, userTaskGrid },
}
</script>
