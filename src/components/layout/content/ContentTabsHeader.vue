<template>
  <div>
    <v-row v-if="isTopology" dense justify="space-between">
      <topology-title />
      <topology-action-buttons />
    </v-row>

    <v-row v-else dense>
      <v-col cols="12">
        <h1 class="headline font-weight-bold">
          {{ title }}
        </h1>
      </v-col>
    </v-row>

    <v-row dense>
      <v-col cols="12" lg="9">
        <v-tabs height="24">
          <v-tabs-slider color="primary" />
          <v-tab
            v-for="(tab, index) in tabs"
            :key="index"
            :to="{ name: tab.route }"
            class="text-transform-none mr-2 body-2 font-weight-medium primary--text"
          >
            <span>{{ $t(tab.name) }}</span>
          </v-tab>
        </v-tabs>
      </v-col>
      <v-col
        v-if="isTopology && isCrone && topologyActive.enabled"
        cols="12"
        lg="3"
        class="d-flex justify-lg-end"
      >
        <span class="mr-5">{{ $t("pages.nextRun") }}: </span>
        <span :key="now.getMilliseconds()" class="font-weight-bold">
          {{ nextRun(topologyActive.cronSettings) }}
        </span>
      </v-col>
    </v-row>
  </div>
</template>

<script>
import TopologyTitle from "@/components/app/topology/landing/TopologyTitle"
import TopologyActionButtons from "@/components/app/topology/landing/TopologyActionButtons"
import moment from "moment"
import { ROUTES } from "@/services/enums/routerEnums"
import { TOPOLOGY_ENUMS } from "@/services/enums/topologyEnums"
import { TOPOLOGIES } from "@/store/modules/topologies/types"
import { mapGetters } from "vuex"
import cronParser from "cron-parser"
import { REQUESTS_STATE } from "@/store/modules/api/types"
import { API } from "@/api"

export default {
  name: "ContentTabsHeader",
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
      default: "",
    },
  },
  data() {
    return {
      TOPOLOGY_ENUMS,
      cronParser,
      timer: null,
      now: new Date(),
    }
  },
  created() {
    this.startRefreshNextRun()
  },
  computed: {
    ...mapGetters(TOPOLOGIES.NAMESPACE, {
      topologyActive: TOPOLOGIES.GETTERS.GET_ACTIVE_TOPOLOGY,
    }),
    ...mapGetters(REQUESTS_STATE.NAMESPACE, [REQUESTS_STATE.GETTERS.GET_STATE]),
    isLoading() {
      return this[REQUESTS_STATE.GETTERS.GET_STATE]([API.topology.getById.id])
        .isSending
    },
    isCrone() {
      if (
        this.$route.matched.some(
          (route) => route.name === ROUTES.TOPOLOGY.DEFAULT
        )
      ) {
        return this.topologyActive?.type === TOPOLOGY_ENUMS.CRON
      }
      return false
    },
  },
  methods: {
    nextRun(cronSettings) {
      if (!cronSettings.length) {
        return "Cron is not set"
      }
      let next = []
      cronSettings.forEach((item) => {
        let interval = this.cronParser.parseExpression(item.cron)
        next.push(interval.next().toString().slice(0, 24))
      })
      next
        .map(function (s) {
          return moment(s, "ddd MMM DD YYYY HH:mm:ss")
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
      return moment(next[next.length - 1]).format("DD. MM. YYYY HH:mm")
    },
    refresh() {
      this.now = new Date()
    },
    startRefreshNextRun() {
      if (this.isTopology && this.isCrone && this.topologyActive?.enabled) {
        this.timer = setInterval(this.refresh, 60_000)
      } else clearInterval(this.timer)
    },
  },
  beforeDestroy() {
    clearInterval(this.timer)
  },
  watch: {
    topologyActive() {
      this.startRefreshNextRun()
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
}

.tabs-span {
  color: var(--v-text-base) !important;
}
</style>
