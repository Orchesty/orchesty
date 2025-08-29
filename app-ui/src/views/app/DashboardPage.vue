<template>
  <content-basic :title="$t('navigation.home')">
    <topology-dashboard />
  </content-basic>
</template>

<script>
import TopologyDashboard from "@/components/app/dashboard/landingComponent/TopologyDashboard.vue"
import ContentBasic from "@/components/layout/content/ContentBasic.vue"

export default {
  name: "DashboardPage",
  components: { ContentBasic, TopologyDashboard },
}
</script>
