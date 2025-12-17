<template>
  <content-basic :title="$t('navigation.scheduledTask')">
    <scheduled-task-grid />
  </content-basic>
</template>

<script>
import ContentBasic from "../../components/layout/content/ContentBasic.vue"
import ScheduledTaskGrid from "../../components/app/scheduledTask/grid/ScheduledTaskGrid.vue"

export default {
  name: "ScheduledTaskPage",
  components: { ScheduledTaskGrid, ContentBasic },
}
</script>
