<template>
  <v-container fluid class="sticky-wrapper">
    <v-row>
      <v-col cols="12" class="pr-0 mt-5 pl-12">
        <h4>{{ $t('topologies.sidebar.title') }}</h4>
      </v-col>
    </v-row>
    <v-row>
      <v-col cols="12" class="pr-0">
        <topology-tree-view />
      </v-col>
    </v-row>
  </v-container>
</template>

<script>
import TopologyTreeView from '@/components/app/topology/treeview/TopologyTreeView'

export default {
  name: 'Sidebar',
  components: {
    TopologyTreeView,
  },
}
</script>
<style lang="scss">
.sticky-wrapper {
  position: sticky;
  top: 0;
}
</style>
