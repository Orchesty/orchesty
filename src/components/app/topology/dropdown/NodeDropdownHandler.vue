<template>
  <div>
    <node-dropdown
      v-for="event in events"
      :key="event._id"
      :data="getCurrentlySelectedNodeData(event)"
      @toggleStatus="onToggleStatus(event._id)"
      @runTopology="onRunTopology(event._id)"
      @clickAway="onCloseDropdown"
    >
      <div v-if="event.type === 'start'">
        <div class="node-run-url mb-2">
          {{ getNodeRunUrl }}
        </div>

        <div>
          <a
            href=""
            class="action-link info--text font-weight-bold"
            @click.prevent="copyStartingPointToClipboard"
          >
            {{ $t("button.copyUrl") }}
          </a>
        </div>
      </div>
      <div v-else>
        <div class="mb-2" :class="{ 'text--disabled': !isNodeRunnable }">
          {{ $t("page.status.nextRun") }}:
          {{ getParsedCronTime(event.cron_time) }}
        </div>
        <template v-if="isEditing">
          <cron-properties-form
            class="pt-1"
            :time="event.cron_time"
            :params="event.cron_params"
            :node-id="data._id"
            @cancel="onCronEditCancel"
            @done="onCronEditDone"
          />
        </template>
        <template v-else>
          <div class="mb-2">
            {{ $t("page.status.cronTime") }}: {{ event.cron_time }}
          </div>
          <div class="mb-2">
            {{ $t("page.status.cronParams") }}: {{ event.cron_params }}
          </div>
          <div>
            <a
              href=""
              class="action-link info--text font-weight-bold"
              @click.prevent="onCronEditOpen"
            >
              {{ $t("button.edit") }}
            </a>
          </div>
        </template>
      </div>
    </node-dropdown>
  </div>
</template>

<script>
import { mapActions, mapGetters } from "vuex"
import { TOPOLOGIES } from "@/store/modules/topologies/types"
import { config } from "@/config"
import FlashMessageMixin from "@/services/mixins/FlashMessageMixin"
import { internationalFormat } from "@/services/utils/dateFilters"
import cronParser from "cron-parser"
import NodeDropdown from "@/components/app/topology/dropdown/NodeDropdown"
import CronPropertiesForm from "./CronPropertiesForm"

export default {
  name: "NodeDropdownHandler",
  components: { NodeDropdown, CronPropertiesForm },
  mixins: [FlashMessageMixin],
  props: {
    data: {
      type: Object,
      default: () => {},
    },
  },
  data() {
    return {
      cronParser,
      internationalFormat,
      isEditing: false,
    }
  },
  computed: {
    runTopologyRequestBody() {
      return {
        body: "{}",
        startingPoints: [this.data._id],
        topologyID: this.topologyActive._id,
      }
    },
    isNodeRunnable() {
      return (
        this.topologyActive.visibility === "public" &&
        this.data.enabled === true &&
        this.topologyActive.enabled === true
      )
    },
    getNodeRunUrl() {
      return `${config.backend.apiBaseUrl}/topologies/${this.topologyActive._id}/nodes/${this.data._id}/run`
    },
    ...mapGetters(TOPOLOGIES.NAMESPACE, {
      events: TOPOLOGIES.GETTERS.GET_STARTING_POINT_EVENTS,
      topologyActive: TOPOLOGIES.GETTERS.GET_ACTIVE_TOPOLOGY,
    }),
  },
  methods: {
    ...mapActions(TOPOLOGIES.NAMESPACE, [
      TOPOLOGIES.ACTIONS.TOPOLOGY.RUN,
      TOPOLOGIES.ACTIONS.NODE.UPDATE,
      TOPOLOGIES.ACTIONS.TOPOLOGY.GET_BY_ID,
      TOPOLOGIES.ACTIONS.TOPOLOGY.NODES,
    ]),
    async onRunTopology() {
      await this[TOPOLOGIES.ACTIONS.TOPOLOGY.RUN](this.runTopologyRequestBody)
      this.$emit("closeDropdown")
    },
    async onToggleStatus() {
      await this[TOPOLOGIES.ACTIONS.NODE.UPDATE]({
        nodeId: this.data._id,
        enabled: !this.data.enabled,
        topologyId: this.topologyActive._id,
      })
      this.$emit("closeDropdown")
      await this[TOPOLOGIES.ACTIONS.TOPOLOGY.GET_BY_ID](this.topologyActive._id)
    },
    getCurrentlySelectedNodeData(event) {
      if (this.data._id === event._id) {
        return { ...this.data, isRunnable: this.isNodeRunnable }
      } else {
        return null
      }
    },
    copyStartingPointToClipboard() {
      navigator.clipboard.writeText(this.getNodeRunUrl)
      this.showFlashMessage(false, this.$t("flashMessages.copyStartingPoint"))
    },
    getParsedCronTime(time) {
      if (!time) {
        return ""
      }
      let interval = this.cronParser.parseExpression(time)
      interval = interval.next().toString().slice(0, 24)
      return this.internationalFormat(interval)
    },
    onCronEditCancel() {
      this.isEditing = false
    },
    onCronEditOpen() {
      this.isEditing = true
    },
    async onCronEditDone() {
      this.isEditing = false
      await this[TOPOLOGIES.ACTIONS.TOPOLOGY.GET_BY_ID](this.topologyActive._id)
    },
    onCloseDropdown() {
      this.isEditing = false
      this.$emit("closeDropdown")
    },
  },
}
</script>

<style scoped lang="scss">
.node-run-url {
  overflow-wrap: anywhere;
}
</style>
