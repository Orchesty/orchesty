<template>
  <content-basic :title="$t('logs.title')">
    <logs-grid />
  </content-basic>
</template>

<script>
import ContentBasic from '@/components/layout/content/ContentBasic'
import LogsGrid from '@/components/app/logs/grid/LogsGrid'
export default {
  name: 'LogPage',
  components: { LogsGrid, ContentBasic },
}
</script>
