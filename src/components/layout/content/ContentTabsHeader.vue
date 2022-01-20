<template>
  <div>
    <v-row v-if="isTopology && topology" dense>
      <topology-title :topology="topology" />
      <topology-action-buttons :topology="topology" />
    </v-row>

    <v-row v-else dense>
      <v-col cols="12">
        <h1 class="headline font-weight-bold">
          {{ title }}
        </h1>
      </v-col>
    </v-row>

    <v-row v-if="!showTabs" dense>
      <v-col cols="12" lg="9">
        <v-tabs v-model="currentPage" height="24">
          <v-tabs-slider color="primary" />
          <v-tab v-for="(tab, index) in tabs" :key="index" :to="{ name: tab.route }" class="text-transform-none mr-2">
            <span>{{ $t(tab.name) }}</span>
          </v-tab>
        </v-tabs>
      </v-col>
      <v-col v-if="isCrone" cols="12" lg="3" class="d-flex justify-end">
        <span class="mr-5">{{ $t('pages.nextRun') }}: </span>
        <span class="font-weight-bold"> {{ nextRun(topology.cronSettings) }} </span>
      </v-col>
    </v-row>
  </div>
</template>

<script>
import TopologyTitle from '@/components/app/topology/landingComponent/TopologyTitle'
import TopologyActionButtons from '@/components/app/topology/landingComponent/TopologyActionButtons'
import moment from 'moment'
import { ROUTES } from '@/services/enums/routerEnums'
import { TOPOLOGY_ENUMS } from '@/services/enums/topologyEnums'
import { TOPOLOGIES } from '@/store/modules/topologies/types'
import { mapActions, mapGetters, mapState } from 'vuex'
import cronParser from 'cron-parser'
import { REQUESTS_STATE } from '@/store/modules/api/types'
import { API } from '@/api'

export default {
  name: 'ContentTabsHeader',
  components: { TopologyActionButtons, TopologyTitle },
  props: {
    tabs: {
      type: Array,
      required: true,
    },
    isTopology: {
      type: Boolean,
      default: false,
    },
    title: {
      type: String,
      required: false,
      default: '',
    },
  },
  data() {
    return {
      TOPOLOGY_ENUMS,
      cronParser,
      showTabs: false,
      currentPage: null,
    }
  },
  computed: {
    ...mapState(TOPOLOGIES.NAMESPACE, ['topology']),
    ...mapGetters(REQUESTS_STATE.NAMESPACE, [REQUESTS_STATE.GETTERS.GET_STATE]),
    isLoading() {
      return this[REQUESTS_STATE.GETTERS.GET_STATE]([API.topology.get.id]).isSending
    },
    isCrone() {
      if (this.$route.matched.some((route) => route.name === ROUTES.TOPOLOGY.DEFAULT)) {
        return this.topology?.type === TOPOLOGY_ENUMS.CRON
      }
      return false
    },
    isApp() {
      return this.$route.name === ROUTES.APP_STORE.INSTALLED_APP || this.$route.name === ROUTES.APP_STORE.DETAIL_APP
    },
  },
  methods: {
    ...mapActions(TOPOLOGIES.NAMESPACE, [TOPOLOGIES.ACTIONS.TOPOLOGY.NODES, TOPOLOGIES.ACTIONS.TOPOLOGY.GET_BY_ID]),
    nextRun(cronSettings) {
      let next = []
      cronSettings.forEach((item) => {
        let interval = this.cronParser.parseExpression(item.cron)
        next.push(interval.next().toString().slice(0, 24))
      })
      next
        .map(function (s) {
          return moment(s, 'ddd MMM DD YYYY HH:mm:ss')
        })
        .sort(function (m) {
          return m.valueOf()
        })
        .find(function (m) {
          return m.isAfter()
        })
      next.sort(function (left, right) {
        return moment.utc(left.timeStamp).diff(moment.utc(right.timeStamp))
      })
      return moment(next[next.length - 1]).format('DD. MM. YYYY HH:mm')
    },
  },
  watch: {
    $route: {
      immediate: true,
      async handler(route) {
        if (this.isTopology) await this[TOPOLOGIES.ACTIONS.TOPOLOGY.GET_BY_ID]({ id: route.params.id })
        this.showTabs = this.isApp
      },
    },
    topology: {
      deep: true,
      immediate: true,
      async handler() {
        if (this.topology) {
          await this[TOPOLOGIES.ACTIONS.TOPOLOGY.NODES]({ id: this.topology._id })
        }
      },
    },
  },
}
</script>

<style scoped>
.text-transform-none {
  text-align: start;
  text-transform: none;
  letter-spacing: 0;
  padding: 0;
  font-size: 0.95em;
}
.tabs-span {
  color: var(--v-text-base) !important;
}
</style>
