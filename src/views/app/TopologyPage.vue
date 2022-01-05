<template>
  <side-page-layout :title="$t('navigation.topologies')">
    <template #sidebar>
      <side-bar-handler />
    </template>
    <template #default>
      <topology-handler />
    </template>
  </side-page-layout>
</template>

<script>
import SidePageLayout from '../../components/layout/SidePageLayout'
import SideBarHandler from '../../components/layout/SideBarHandler'
import TopologyHandler from '../../components/app/topology/landingComponent/TopologyHandler'

export default {
  name: 'TopologyPage',
  components: { TopologyHandler, SideBarHandler, SidePageLayout },
}
</script>
