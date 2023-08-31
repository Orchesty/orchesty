<template>
  <content-basic :title="$t('navigation.logs')">
    <logs-grid />
  </content-basic>
</template>

<script>
import ContentBasic from "@/components/layout/content/ContentBasic"
import LogsGrid from "@/components/app/logs/grid/LogsGrid"
export default {
  name: "LogPage",
  components: { LogsGrid, ContentBasic },
}
</script>
