<template>
  <side-page-layout :title="$t('navigation.topologies')">
    <template #sidebar>
      <side-bar-handler />
    </template>
    <template #default>
      <page-layout :title="$t('navigation.home')">
        <topology-dashboard />
      </page-layout>
    </template>
  </side-page-layout>
</template>

<script>
import PageLayout from '../../components/layout/PageLayout'
import SideBarHandler from '../../components/layout/SideBarHandler'
import SidePageLayout from '../../components/layout/SidePageLayout'
import TopologyDashboard from '@/components/app/dashboard/landingComponent/TopologyDashboard'

export default {
  name: 'DashboardPage',
  components: { TopologyDashboard, PageLayout, SideBarHandler, SidePageLayout },
}
</script>
