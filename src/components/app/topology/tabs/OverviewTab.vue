<template>
  <div v-if="topologyActive._id">
    <data-grid
      ref="grid"
      disable-filter
      :is-loading="state.isSending"
      :namespace="DATA_GRIDS.OVERVIEW"
      :quick-filters="quickFilters"
      :headers="headers"
      :fixed-params="{ id: topologyActive._id }"
    >
      <template #default="{ items, isVisible }">
        <td v-if="isVisible('started')">
          {{ internationalFormat(items.item.started) }}
        </td>
        <td v-if="isVisible('duration')">
          {{ getProcessDurationTime(items.item) }}
        </td>
        <td v-if="isVisible('progress')">
          {{ items.item.nodesProcessed + "/" + items.item.nodesTotal }}
        </td>
        <td v-if="isVisible('status')" class="font-weight-bold">
          <span
            :class="`text-uppercase ${getStatusColor(items.item.status)}--text`"
          >
            {{ items.item.status }}
          </span>
        </td>
        <td v-if="isVisible('correlation_id')">
          <v-btn
            v-if="items.item.correlationId"
            icon
            @click.stop="copyToClipboard(items.item.correlationId)"
          >
            <app-icon>mdi-content-copy</app-icon>
          </v-btn>
        </td>
      </template>
    </data-grid>
  </div>
</template>

<script>
import { internationalFormat } from "@/services/utils/dateFilters"
import { DATA_GRIDS } from "@/services/enums/dataGridEnums"
import DataGrid from "@/components/commons/grid/DataGrid"
import { REQUESTS_STATE } from "@/store/modules/api/types"
import { API } from "@/api"
import { mapGetters } from "vuex"
import { TOPOLOGIES } from "@/store/modules/topologies/types"
import { GRID } from "@/store/modules/grid/types"
import prettyMilliseconds from "pretty-ms"
import FlashMessageMixin from "@/services/mixins/FlashMessageMixin"
import QuickFiltersMixin from "@/services/mixins/QuickFiltersMixin"
import AppIcon from "@/components/commons/icon/AppIcon"
import moment from "moment"

export default {
  name: "OverviewTab",
  components: { AppIcon, DataGrid },
  mixins: [FlashMessageMixin, QuickFiltersMixin],
  computed: {
    ...mapGetters(DATA_GRIDS.OVERVIEW, {
      sorterInitial: GRID.GETTERS.GET_SORTER,
      pagingInitial: GRID.GETTERS.GET_PAGING,
    }),
    ...mapGetters(REQUESTS_STATE.NAMESPACE, [REQUESTS_STATE.GETTERS.GET_STATE]),
    ...mapGetters(TOPOLOGIES.NAMESPACE, {
      topologyActive: TOPOLOGIES.GETTERS.GET_ACTIVE_TOPOLOGY,
    }),
    state() {
      return this[REQUESTS_STATE.GETTERS.GET_STATE](API.overview.grid.id)
    },
  },
  data() {
    return {
      DATA_GRIDS,
      internationalFormat,
      headers: [
        {
          text: this.$t("grid.header.created"),
          value: "started",
          align: "left",
          sortable: true,
          visible: true,
          width: "15%",
        },
        {
          text: this.$t("grid.header.duration"),
          value: "duration",
          align: "left",
          sortable: false,
          visible: true,
          width: "15%",
        },
        {
          text: this.$t("grid.header.progress"),
          value: "progress",
          align: "left",
          sortable: false,
          visible: true,
          width: "15%",
        },
        {
          text: this.$t("grid.header.status"),
          value: "status",
          align: "left",
          sortable: false,
          visible: true,
          width: "15%",
        },
        {
          text: this.$t("grid.header.correlation_id"),
          value: "correlation_id",
          align: "left",
          sortable: true,
          visible: true,
          width: "15%",
        },
      ],
    }
  },
  methods: {
    prettyMs: prettyMilliseconds,

    getProcessFinishTime(process) {
      return this.isInProgress(process.status)
        ? "-"
        : internationalFormat(process.finished)
    },
    getProcessDurationTime(process) {
      if (this.isInProgress(process.status)) {
        const processStartedMilliseconds = moment(process.started).format("x")
        const currentTimeMilliseconds = moment().format("x")

        if (currentTimeMilliseconds - processStartedMilliseconds < 0) {
          return "Invalid computation time"
        }

        return this.prettifyMilliseconds(
          currentTimeMilliseconds - processStartedMilliseconds
        )
      } else {
        return this.prettifyMilliseconds(process.duration)
      }
    },
    getStatusColor(props) {
      if (props.toLowerCase() === "failed") {
        return "error"
      }
      if (props.toLowerCase() === "in progress") {
        return "black"
      }
      if (props.toLowerCase() === "success") {
        return "success"
      }
      return "info"
    },

    copyToClipboard(correlationId) {
      navigator.clipboard.writeText(correlationId)
      this.showFlashMessage(false, this.$t("flashMessages.topologies.idCopied"))
    },

    isInProgress(value) {
      return value.toLowerCase() === "in progress"
    },

    prettifyMilliseconds(milliseconds) {
      return this.prettyMs(milliseconds, { keepDecimalsOnWholeSeconds: true })
    },
  },
  async mounted() {
    this.init("started")
    await this.$refs.grid.fetchGridWithInitials(
      null,
      null,
      null,
      this.pagingInitial,
      this.sorterInitial
    )
  },
}
</script>
