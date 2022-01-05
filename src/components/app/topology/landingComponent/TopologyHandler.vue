<template>
  <page-tabs-layout is-topology :tabs="tabs" />
</template>

<script>
import PageTabsLayout from '@/components/layout/PageTabsLayout'
import { ROUTES } from '@/router/routes'

export default {
  components: { PageTabsLayout },
  name: 'TopologyHandler',
  data() {
    return {
      topologyData: null,
      tabs: [
        {
          name: 'topologies.detail.tabs.editor',
          route: ROUTES.TOPOLOGIES.EDITOR,
        },
        {
          name: 'topologies.detail.tabs.overview',
          route: ROUTES.TOPOLOGIES.OVERVIEW,
        },
        {
          name: 'topologies.detail.tabs.statistic',
          route: ROUTES.TOPOLOGIES.STATISTIC,
        },
        {
          name: 'topologies.detail.tabs.userTask',
          route: ROUTES.TOPOLOGIES.USER_TASK,
        },
        {
          name: 'topologies.detail.tabs.logs',
          route: ROUTES.TOPOLOGIES.LOGS,
        },
      ],
    }
  },
}
</script>
