<template>
  <content-tabs v-if="topologyActive" is-topology :tabs="tabs" />
  <topology-no-selection v-else />
</template>

<script>
import ContentTabs from '@/components/layout/content/ContentTabs'
import { ROUTES } from '@/services/enums/routerEnums'
import { mapGetters } from 'vuex'
import { TOPOLOGIES } from '@/store/modules/topologies/types'
import TopologyNoSelection from '@/components/app/topology/landing/TopologyNoSelection'

export default {
  components: { TopologyNoSelection, ContentTabs },
  name: 'TopologyPage',
  computed: {
    ...mapGetters(TOPOLOGIES.NAMESPACE, { topologyActive: TOPOLOGIES.GETTERS.GET_ACTIVE_TOPOLOGY }),
  },
  data() {
    return {
      tabs: [
        {
          name: 'topologies.detail.tabs.overview',
          route: ROUTES.TOPOLOGY.VIEWER,
        },
        {
          name: 'topologies.detail.tabs.processes',
          route: ROUTES.TOPOLOGY.OVERVIEW,
        },
        {
          name: 'topologies.detail.tabs.statistic',
          route: ROUTES.TOPOLOGY.STATISTIC,
        },
        {
          name: 'topologies.detail.tabs.userTask',
          route: ROUTES.TOPOLOGY.USER_TASK,
        },
        {
          name: 'topologies.detail.tabs.logs',
          route: ROUTES.TOPOLOGY.LOGS,
        },
      ],
    }
  },
}
</script>
