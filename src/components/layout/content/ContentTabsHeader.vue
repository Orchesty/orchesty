<template>
  <div>
    <v-row v-if="isTopology" dense justify="space-between">
      <topology-title />
      <topology-action-buttons />
    </v-row>
    <v-row v-else-if="isAppStore" dense justify="space-between">
      <app-store-action-buttons />
    </v-row>
    <v-row v-else dense>
      <v-col cols="12">
        <h1 class="headline font-weight-bold">
          {{ title }}
        </h1>
      </v-col>
    </v-row>

    <v-row dense class="mb-3">
      <v-col cols="12" lg="9">
        <v-tabs height="40">
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
        <span class="mr-5">{{ $t("page.status.nextRun") }}: </span>
        <span :key="now.getMilliseconds()" class="font-weight-bold">
          {{ nextRun(topologyActive.cronSettings) }}
        </span>
      </v-col>
    </v-row>
  </div>
</template>

<script>
import TopologyTitle from "@/components/app/topology/landing/TopologyTitle.vue"
import TopologyActionButtons from "@/components/app/topology/landing/TopologyActionButtons.vue"
import moment from "moment"
import { ROUTES } from "@/services/enums/routerEnums"
import { TOPOLOGY_ENUMS } from "@/services/enums/topologyEnums"
import { TOPOLOGIES } from "@/store/modules/topologies/types"
import { mapGetters } from "vuex"
import cronParser from "cron-parser"
import { REQUESTS_STATE } from "@/store/modules/api/types"
import { API } from "@/api"
import AppStoreActionButtons from "@/components/app/appStore/landing/AppStoreActionButtons.vue"
import { internationalFormat } from "@/services/utils/dateFilters"

export default {
  name: "ContentTabsHeader",
  components: { AppStoreActionButtons, TopologyActionButtons, TopologyTitle },
  props: {
    tabs: {
      type: Array,
      required: true,
    },
    isTopology: {
      type: Boolean,
      default: false,
    },
    isAppStore: {
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
      internationalFormat,
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
          (route) => route.name === ROUTES.TOPOLOGY.DEFAULT,
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
        next.push(moment(this.cronParser.parse(item.cron).next().toISOString()))
      })

      return this.internationalFormat(
        next.sort((left, right) => left.diff(right))[0],
      )
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
