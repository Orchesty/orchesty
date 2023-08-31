<template>
  <v-col cols="12">
    <data-grid
      ref="bpmnNodeGrid"
      :height="'300'"
      :headers="headers"
      :is-loading="logState.isSending"
      :namespace="DATA_GRIDS.NODE_LOGS"
      expand-click
      :title="$t('page.heading.logs')"
      :show-expand="nodeStatus"
      :placeholder="!nodeStatus"
      :permanent-filter="
        [
          [
            {
              column: 'topology_id',
              operator: 'EQUAL',
              value: [''],
              default: true,
            },
          ],
          [
            {
              column: 'node_id',
              operator: 'EQUAL',
              value: [''],
              default: true,
            },
          ],
        ].concat(filter)
      "
      disable-filter
      disable-search
      :request-params="{ nodeID: node._id, topologyID: $route.params.id }"
      disabled-advanced-filter
    >
      <template #top>
        <v-container fluid>
          <v-row>
            <v-col cols="4" lg="4" class="d-flex">
              <div class="my-auto">
                <span class="font-weight-bold">Node id: </span>
                <span>{{ node ? node._id : "" }}</span>
              </div>
            </v-col>
            <v-col cols="6" lg="6" class="d-flex">
              <div class="my-auto">
                <span class="font-weight-bold">Node name: </span>
                <span class="text-uppercase truncate">{{
                  node ? node.name : ""
                }}</span>
              </div>
            </v-col>
            <v-col>
              <div class="text-end">
                <v-btn text class="ml-auto" @click="$emit('closeLogs')"
                  >Close</v-btn
                >
              </div>
            </v-col>
          </v-row>
        </v-container>
      </template>
      <template v-if="!nodeStatus" #body>
        <td :colspan="headers.length">
          <div class="d-flex py-5">
            <span class="ma-auto">No node selected</span>
          </div>
        </td>
      </template>
      <template #expand="{ items }">
        <span>{{ items.item.message }}</span>
      </template>
      <template #default="{ items, isVisible, expanded }">
        <td
          v-if="isVisible('timestamp')"
          :style="expanded ? 'border-bottom: none' : ''"
        >
          {{ items.item.timestamp | internationalFormat }}
        </td>
        <tooltip>
          <template #activator="{ on, attrs }">
            <td
              v-if="isVisible('correlation_id')"
              v-bind="attrs"
              :style="expanded ? 'border-bottom: none' : ''"
              v-on="on"
            >
              {{
                items.item.correlation_id
                  ? items.item.correlation_id
                  : "system log - no id"
              }}
              <v-btn
                v-if="items.item.correlation_id"
                icon
                @click.stop="copyToClipboard(items.item.correlation_id)"
              >
                <v-icon> mdi-content-copy </v-icon>
              </v-btn>
            </td>
          </template>
          <template #tooltip>
            {{
              items.item.correlation_id
                ? items.item.correlation_id
                : "system log - no id"
            }}
          </template>
        </tooltip>
        <td
          v-if="isVisible('message')"
          :style="expanded ? 'border-bottom: none;' : ''"
          class="truncate"
        >
          <span>
            {{ items.item.message }}
          </span>
        </td>
        <td
          v-if="isVisible('severity')"
          :style="expanded ? 'border-bottom: none' : ''"
        >
          <span class="font-weight-bold text-uppercase">{{
            items.item.severity
          }}</span>
        </td>
      </template>
      <template #footer>
        <v-container>
          <v-row>
            <v-col cols="12" class="d-flex">
              <v-btn
                color="secondary"
                text
                class="font-weight-medium ma-auto"
                @click="redirectToLogs"
              >
                {{ $t("page.text.viewAllLogs") }}
              </v-btn>
            </v-col>
          </v-row>
        </v-container>
      </template>
    </data-grid>
  </v-col>
</template>

<script>
import DataGrid from "@/components/commons/grid/DataGrid"
import { ROUTES } from "@/services/enums/routerEnums"
import { mapGetters } from "vuex"
import { REQUESTS_STATE } from "@/store/modules/api/types"
import { API } from "@/api"
import { DATA_GRIDS } from "@/services/enums/dataGridEnums"
import { internationalFormat } from "@/services/utils/dateFilters"
import Tooltip from "@/components/commons/Tooltip"
import FlashMessageMixin from "@/services/mixins/FlashMessageMixin"

export default {
  name: "BpmnNodeGrid",
  components: { Tooltip, DataGrid },
  mixins: [FlashMessageMixin],
  props: {
    node: {
      type: Object,
      required: true,
    },
    filter: {
      type: Array,
      required: true,
    },
  },
  data() {
    return {
      DATA_GRIDS,
      mergedFilter: [],
      headers: [
        {
          text: this.$t("grid.header.timestamp"),
          value: "timestamp",
          align: "left",
          sortable: true,
          visible: true,
          width: "15%",
        },
        {
          text: this.$t("grid.header.correlation_id"),
          value: "correlation_id",
          align: "left",
          sortable: true,
          visible: true,
          width: "20%",
        },
        {
          text: this.$t("grid.header.message"),
          value: "message",
          align: "left",
          sortable: true,
          visible: true,
          width: "45%",
        },
        {
          text: this.$t("grid.header.severity"),
          value: "severity",
          align: "left",
          sortable: true,
          visible: true,
          width: "20%",
        },
      ],
    }
  },
  computed: {
    ...mapGetters(REQUESTS_STATE.NAMESPACE, [REQUESTS_STATE.GETTERS.GET_STATE]),
    logState() {
      return this[REQUESTS_STATE.GETTERS.GET_STATE]([
        API.topology.getLogsByID.id,
      ])
    },
    nodeStatus() {
      return Object.keys(this.node).length !== 0
    },
  },
  filters: {
    internationalFormat,
  },
  methods: {
    setColor(props) {
      switch (props.toLowerCase()) {
        case "error":
          return "error"
        case "warning":
          return "warning"
        case "ok":
          return "info"
        default:
          return "black"
      }
    },
    copyToClipboard(correlationId) {
      navigator.clipboard.writeText(correlationId)
      this.showFlashMessage(false, "ID copied!")
    },
    async redirectToLogs() {
      await this.$router.push({
        name: ROUTES.TOPOLOGY.LOGS,
        params: { id: this.$route.params.id },
      })
    },
  },
  watch: {
    node: {
      deep: true,
      async handler() {
        await this.$refs.bpmnNodeGrid.fetchGridWithFilter()
      },
    },
  },
}
</script>

<style scoped></style>
