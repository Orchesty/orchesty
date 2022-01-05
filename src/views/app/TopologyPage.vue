<template>
  <content-tabs is-topology :tabs="tabs" />
</template>

<script>
import ContentTabs from '@/components/layout/content/ContentTabs'
import { ROUTES } from '@/services/enums/routerEnums'

export default {
  components: { ContentTabs },
  name: 'TopologyPage',
  data() {
    return {
      topologyData: null,
      tabs: [
        {
          name: 'topologies.detail.tabs.editor',
          route: ROUTES.TOPOLOGY.VIEWER,
        },
        {
          name: 'topologies.detail.tabs.overview',
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
