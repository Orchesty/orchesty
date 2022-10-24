<template>
  <content-basic :title="$t('navigation.home')">
    <topology-dashboard />
  </content-basic>
</template>

<script>
import TopologyDashboard from "@/components/app/dashboard/landingComponent/TopologyDashboard"
import ContentBasic from "@/components/layout/content/ContentBasic"

export default {
  name: "DashboardPage",
  components: { ContentBasic, TopologyDashboard },
}
</script>
