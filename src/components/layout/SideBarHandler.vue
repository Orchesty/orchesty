<template>
  <v-container fluid class="sidebar-wrapper">
    <v-row>
      <v-col cols="12" class="pr-0 mt-5 pl-12">
        <h4>{{ $t('topologies.sidebar.title') }}</h4>
      </v-col>
    </v-row>
    <v-row>
      <v-col cols="12" class="pr-0">
        <topology-tree-handler />
      </v-col>
    </v-row>
  </v-container>
</template>

<script>
import TopologyTreeHandler from '@/components/app/topology/treeview/TopologyTreeHandler'

export default {
  name: 'SideBarHandler',
  components: {
    TopologyTreeHandler,
  },
}
</script>
<style lang="scss">
.sidebar-wrapper {
  position: sticky;
  top: 0;
}
</style>
