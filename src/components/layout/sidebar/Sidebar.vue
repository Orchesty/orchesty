<template>
  <v-container fluid>
    <v-row class="pt-0">
      <v-col cols="12" class="px-0">
        <div class="d-flex">
          <h1 class="ml-4 headline font-weight-bold">{{ $t('topologies.sidebar.title') }}</h1>
        </div>
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
<style lang="scss"></style>
