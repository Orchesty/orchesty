<template>
  <v-col cols="auto" class="d-flex justify-end align-center ml-auto">
    <div class="mr-3">
      {{ $t('page.status.status') }}: <span class="font-weight-bold info--text">{{ topologyStatus }}</span>
    </div>

    <div class="mr-3 mr-md-5">
      <app-icon-with-text-button
        v-if="!topologyActive.enabled && topologyActive.visibility === PAGE_TABS_ENUMS.PUBLIC"
        :loading="enableState"
        :disabled="isSending"
        :text="$t('button.enable')"
        @click="enable"
      >
        <template #icon>
          <app-icon color="gray"> mdi-play </app-icon>
        </template>
      </app-icon-with-text-button>

      <app-icon-with-text-button
        v-if="topologyActive.enabled && topologyActive.visibility === PAGE_TABS_ENUMS.PUBLIC"
        :loading="disableState"
        :disabled="isSending"
        :text="$t('button.disable')"
        @click="disable"
      >
        <template #icon>
          <app-icon color="gray"> mdi-pause </app-icon>
        </template>
      </app-icon-with-text-button>

      <app-icon-with-text-button
        v-if="topologyActive.visibility !== PAGE_TABS_ENUMS.PUBLIC"
        :loading="publishState"
        :disabled="isSending"
        :text="$t('button.publish')"
        @click="publish"
      >
        <template #icon>
          <app-icon color="gray"> mdi-publish </app-icon>
        </template>
      </app-icon-with-text-button>
    </div>

    <tooltip>
      <template #activator="{ on, attrs }">
        <app-button
          :attrs="attrs"
          icon
          :class="buttonClass"
          :disabled="!topologyActive.enabled || topologyActive.visibility !== PAGE_TABS_ENUMS.PUBLIC"
          :on="on"
          :on-click="() => events.emit(EVENTS.MODAL.TOPOLOGY.RUN, topologyActive)"
        >
          <template #icon>
            <app-icon color="gray"> mdi-play-circle-outline </app-icon>
          </template>
        </app-button>
      </template>
      <template #tooltip>
        {{ $t('button.run') }}
      </template>
    </tooltip>

    <tooltip>
      <template #activator="{ on, attrs }">
        <app-button
          :class="buttonClass"
          :attrs="attrs"
          :loading="testState"
          :disabled="isSending"
          icon
          :on="on"
          :on-click="test"
        >
          <template #icon>
            <app-icon color="gray"> mdi-check-network-outline </app-icon>
          </template>
        </app-button>
      </template>
      <template #tooltip>
        {{ $t('button.test') }}
      </template>
    </tooltip>

    <tooltip>
      <template #activator="{ on, attrs }">
        <app-button
          :attrs="attrs"
          :class="buttonClass"
          :to="{ name: ROUTES.EDITOR }"
          :disabled="isSending"
          icon
          :on="on"
        >
          <template #icon>
            <app-icon color="gray"> edit </app-icon>
          </template>
        </app-button>
      </template>
      <template #tooltip>
        {{ $t('navigation.editor') }}
      </template>
    </tooltip>

    <topology-detail-menu />
  </v-col>
</template>

<script>
import { TOPOLOGY_ENUMS } from '@/services/enums/topologyEnums'
import { mapActions, mapGetters } from 'vuex'
import { REQUESTS_STATE } from '@/store/modules/api/types'
import { API } from '@/api'
import { TOPOLOGIES } from '@/store/modules/topologies/types'
import { ROUTES } from '@/services/enums/routerEnums'
import axios from 'axios'
import { config } from '@/config'
import { AUTH } from '@/store/modules/auth/types'
import { events, EVENTS } from '@/services/utils/events'
import TopologyDetailMenu from '@/components/app/topology/menu/TopologyDetailMenu'
import Tooltip from '@/components/commons/Tooltip'
import AppButton from '@/components/commons/button/AppButton'
import AppIcon from '@/components/commons/icon/AppIcon'
import { redirectTo } from '@/services/utils/utils'
import AppIconWithTextButton from '@/components/commons/button/AppIconWithTextButton'

export default {
  name: 'TopologyActionButtons',
  components: { AppIconWithTextButton, AppIcon, AppButton, Tooltip, TopologyDetailMenu },
  data() {
    return {
      PAGE_TABS_ENUMS: TOPOLOGY_ENUMS,
      ROUTES,
      EVENTS,
      events,
      startingPoint: '',
      buttonClass: '',
    }
  },
  computed: {
    ...mapGetters(AUTH.NAMESPACE, { loggedUserId: AUTH.GETTERS.GET_LOGGED_USER_ID }),
    ...mapGetters(TOPOLOGIES.NAMESPACE, {
      topologyActive: TOPOLOGIES.GETTERS.GET_ACTIVE_TOPOLOGY,
      topologyActiveNodes: TOPOLOGIES.GETTERS.GET_ACTIVE_TOPOLOGY_NODES,
    }),
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
      return this.topologyActive.enabled && this.topologyActive.visibility === TOPOLOGY_ENUMS.PUBLIC
    },
    enabled() {
      return this.topologyActive.enabled && this.topologyActive.visibility === TOPOLOGY_ENUMS.PUBLIC
    },
    isSending() {
      return this.enableState || this.publishState || this.disableState || this.testState
    },
    topologyStatus() {
      if (this.topologyActive) {
        if (this.topologyActive.visibility === TOPOLOGY_ENUMS.PUBLIC) {
          if (this.topologyActive.enabled) {
            return 'enabled'
          } else {
            return 'disabled'
          }
        } else {
          return TOPOLOGY_ENUMS.DRAFT
        }
      } else {
        return ''
      }
    },
  },
  methods: {
    ...mapActions(TOPOLOGIES.NAMESPACE, [
      TOPOLOGIES.ACTIONS.TOPOLOGY.TEST,
      TOPOLOGIES.ACTIONS.TOPOLOGY.ENABLE,
      TOPOLOGIES.ACTIONS.TOPOLOGY.DISABLE,
      TOPOLOGIES.ACTIONS.TOPOLOGY.PUBLISH,
      TOPOLOGIES.ACTIONS.TOPOLOGY.GET_BY_ID,
      TOPOLOGIES.ACTIONS.DATA.GET_TOPOLOGIES,
    ]),
    async publish() {
      await this.fetchChangesAfterActon(TOPOLOGIES.ACTIONS.TOPOLOGY.PUBLISH)
    },
    async enable() {
      await this.fetchChangesAfterActon(TOPOLOGIES.ACTIONS.TOPOLOGY.ENABLE)
    },
    async disable() {
      await this.fetchChangesAfterActon(TOPOLOGIES.ACTIONS.TOPOLOGY.DISABLE)
    },
    async test() {
      if (this.$route.name !== TOPOLOGY_ENUMS.BPMN_VIEWER) {
        await redirectTo(this.$router, { name: ROUTES.TOPOLOGY.VIEWER })
      }
      await this.fetchChangesAfterActon(TOPOLOGIES.ACTIONS.TOPOLOGY.TEST)
    },
    async fetchChangesAfterActon(action) {
      await this[action](this.topologyActive._id)
      await this[TOPOLOGIES.ACTIONS.TOPOLOGY.GET_BY_ID](this.topologyActive._id)
      await this[TOPOLOGIES.ACTIONS.DATA.GET_TOPOLOGIES](this.topologyActive._id)
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
    getNodeRunUrl(baseURL, nodeId, nodeName, nodeType, topologyId, topologyName, data = {}) {
      return nodeType === 'webhook'
        ? `${baseURL}/topologies/${topologyName}/nodes/${nodeName}/token/${data.token ? data.token : 'token'}/run`
        : `${baseURL}/topologies/${topologyId}/nodes/${nodeId}/run`
    },
    createStartingPoint(item) {
      return this.getNodeRunUrl(
        config.backend.apiStartingPoint,
        item._id,
        item.name,
        item.type,
        item.topology_id,
        this.topologyActive.name
      )
    },
  },
  watch: {
    topologyActiveNodes() {
      let start = this.topologyActiveNodes.filter((node) => node.type === 'start')[0]
      if (!start) return
      this.startingPoint = this.createStartingPoint(start)
    },
  },
}
</script>
