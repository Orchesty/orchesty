<template>
  <v-col cols="12" md="6" class="text-right">
    <tooltip>
      <template #activator="{ on, attrs }">
        <v-btn
          v-bind="attrs"
          icon
          color="secondary"
          class="ml-2 my-1 my-lg-0"
          :disabled="!topology.enabled || topology.visibility !== PAGE_TABS_ENUMS.PUBLIC"
          v-on="on"
          @click="events.emit(EVENTS.MODAL.TOPOLOGY.RUN, { topology })"
        >
          <v-icon> mdi-play-circle-outline </v-icon>
        </v-btn>
      </template>
      <template #tooltip>
        <span class="font-weight-medium"> {{ $t('pages.run') }} </span>
      </template>
    </tooltip>

    <tooltip>
      <template #activator="{ on, attrs }">
        <v-btn
          v-bind="attrs"
          class="ml-1 my-1 my-lg-0"
          :to="{ name: ROUTES.EDITOR }"
          :disabled="isSending"
          icon
          v-on="on"
        >
          <v-icon> mdi-pencil-outline </v-icon>
        </v-btn>
      </template>
      <template #tooltip>
        <span class="font-weight-medium"> {{ $t('pages.editor') }} </span>
      </template>
    </tooltip>

    <tooltip v-if="!topology.enabled && topology.visibility === PAGE_TABS_ENUMS.PUBLIC">
      <template #activator="{ on, attrs }">
        <v-btn
          v-if="!topology.enabled && topology.visibility === PAGE_TABS_ENUMS.PUBLIC"
          v-bind="attrs"
          :loading="enableState"
          class="ml-1 my-1 my-lg-0"
          :disabled="isSending"
          icon
          v-on="on"
          @click="enable"
        >
          <v-icon> mdi-check-circle-outline </v-icon>
        </v-btn>
      </template>
      <template #tooltip>
        <span class="font-weight-medium"> {{ $t('pages.enable') }} </span>
      </template>
    </tooltip>

    <tooltip v-if="topology.enabled && topology.visibility === PAGE_TABS_ENUMS.PUBLIC">
      <template #activator="{ on, attrs }">
        <v-btn
          v-if="topology.enabled && topology.visibility === PAGE_TABS_ENUMS.PUBLIC"
          v-bind="attrs"
          class="ml-1 my-1 my-lg-0"
          :loading="disableState"
          :disabled="isSending"
          icon
          v-on="on"
          @click="disable"
        >
          <v-icon> mdi-cancel </v-icon>
        </v-btn>
      </template>
      <template #tooltip>
        <span class="font-weight-medium"> {{ $t('pages.disable') }} </span>
      </template>
    </tooltip>

    <tooltip v-if="topology.visibility !== PAGE_TABS_ENUMS.PUBLIC">
      <template #activator="{ on, attrs }">
        <v-btn
          v-if="topology.visibility !== PAGE_TABS_ENUMS.PUBLIC"
          v-bind="attrs"
          class="ml-1 my-1 my-lg-0"
          :loading="publishState"
          :disabled="isSending"
          icon
          v-on="on"
          @click="publish"
        >
          <v-icon>mdi-publish</v-icon>
        </v-btn>
      </template>
      <template #tooltip>
        <span class="font-weight-medium"> {{ $t('pages.publish') }} </span>
      </template>
    </tooltip>

    <tooltip>
      <template #activator="{ on, attrs }">
        <v-btn v-bind="attrs" :loading="testState" class="ml-1" :disabled="isSending" icon v-on="on" @click="test">
          <v-icon> mdi-gauge-full </v-icon>
        </v-btn>
      </template>
      <template #tooltip>
        <span class="font-weight-medium"> {{ $t('pages.test') }} </span>
      </template>
    </tooltip>

    <topology-detail-menu :topology="{ ...topology, id: topology._id }" :topologies="topologies" />
  </v-col>
</template>

<script>
import { TOPOLOGY_ENUMS } from '@/services/enums/topologyEnums'
import { mapActions, mapGetters, mapState } from 'vuex'
import { REQUESTS_STATE } from '@/store/modules/api/types'
import { API } from '@/api'
import { TOPOLOGIES } from '@/store/modules/topologies/types'
import { ROUTES } from '@/services/enums/routerEnums'
import axios from 'axios'
import { config } from '@/config'
import { AUTH } from '@/store/modules/auth/types'
import { events, EVENTS } from '@/services/utils/events'
import TopologyDetailMenu from '@/components/app/topology/menu/TopologyDetailMenu'
import Tooltip from '@/components/commons/tooltip/Tooltip'

export default {
  name: 'TopologyActionButtons',
  components: { Tooltip, TopologyDetailMenu },
  props: {
    topology: {
      type: Object,
      required: true,
    },
  },
  data() {
    return {
      PAGE_TABS_ENUMS: TOPOLOGY_ENUMS,
      ROUTES,
      EVENTS,
      events,
      startingPoint: '',
    }
  },
  computed: {
    ...mapState(TOPOLOGIES.NAMESPACE, ['topologies']),
    ...mapState(AUTH.NAMESPACE, ['user']),
    ...mapState(TOPOLOGIES.NAMESPACE, ['nodeNames']),
    ...mapGetters(REQUESTS_STATE.NAMESPACE, [REQUESTS_STATE.GETTERS.GET_STATE]),
    enableState() {
      return this[REQUESTS_STATE.GETTERS.GET_STATE](API.topology.enable.id).isSending
    },
    disableState() {
      return this[REQUESTS_STATE.GETTERS.GET_STATE](API.topology.disable.id).isSending
    },
    publishState() {
      return this[REQUESTS_STATE.GETTERS.GET_STATE](API.topology.publish.id).isSending
    },
    testState() {
      return this[REQUESTS_STATE.GETTERS.GET_STATE](API.topology.test.id).isSending
    },
    canBeRun() {
      return this.topology.enabled && this.topology.visibility === TOPOLOGY_ENUMS.PUBLIC
    },
    enabled() {
      return this.topology.enabled && this.topology.visibility === TOPOLOGY_ENUMS.PUBLIC
    },
    isSending() {
      return this.enableState || this.publishState || this.disableState || this.testState
    },
  },
  methods: {
    ...mapActions(TOPOLOGIES.NAMESPACE, [
      TOPOLOGIES.ACTIONS.TOPOLOGY.TEST,
      TOPOLOGIES.ACTIONS.TOPOLOGY.ENABLE,
      TOPOLOGIES.ACTIONS.TOPOLOGY.DISABLE,
      TOPOLOGIES.ACTIONS.TOPOLOGY.PUBLISH,
    ]),
    async publish() {
      await this[TOPOLOGIES.ACTIONS.TOPOLOGY.PUBLISH]({ topologyID: this.topology._id })
    },
    async enable() {
      await this[TOPOLOGIES.ACTIONS.TOPOLOGY.ENABLE]({ topologyID: this.topology._id })
    },
    async disable() {
      await this[TOPOLOGIES.ACTIONS.TOPOLOGY.DISABLE]({ topologyID: this.topology._id })
    },
    async test() {
      if (this.$route.name !== TOPOLOGY_ENUMS.BPMN_VIEWER) {
        await this.$router.push({ name: ROUTES.TOPOLOGY.VIEWER })
      }
      await this[TOPOLOGIES.ACTIONS.TOPOLOGY.TEST]({ topologyID: this.topology._id })
    },
    async run(item) {
      const options = {
        method: 'post',
        url: this.createStartingPoint(item),
        data: {
          data: {},
        },
        transformResponse: [
          (data) => {
            return data
          },
        ],
      }
      await axios(options)
    },
    getNodeRunUrl(baseURL, nodeId, nodeName, nodeType, topologyId, topologyName, userId, data = {}) {
      return nodeType === 'webhook'
        ? `${baseURL}/topologies/${topologyName}/nodes/${nodeName}/token/${data.token ? data.token : 'token'}/run`
        : `${baseURL}/topologies/${topologyId}/nodes/${nodeId}/user/${userId}/run`
    },
    createStartingPoint(item) {
      return this.getNodeRunUrl(
        config.backend.apiStartingPoint,
        item._id,
        item.name,
        item.type,
        item.topology_id,
        this.topology.name,
        this.user.user.id
      )
    },
  },
  watch: {
    nodeNames() {
      let start = this.nodeNames.filter((node) => node.type === 'start')[0]
      if (!start) return
      this.startingPoint = this.getNodeRunUrl(
        config.backend.apiStartingPoint,
        start._id,
        start.name,
        start.type,
        start.topology_id,
        this.topology.name,
        this.user.user.id
      )
    },
  },
}
</script>

<style scoped></style>
