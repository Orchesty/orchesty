<template>
  <v-col cols="12">
    <data-grid
      ref="grid"
      :quick-filters="quickFilters"
      :is-loading="state.isSending"
      :namespace="DATA_GRIDS.DASHBOARD_PROCESSES"
      :headers="headers"
      :allow-quick-filter-reset="true"
    >
      <template #default="{ items: { item } }">
        <td>
          {{ item.name }}
        </td>
        <td>
          {{ $options.filters.internationalFormat(item.started) }}
        </td>
        <td>
          {{ getProcessDurationTime(item) }}
        </td>
        <td>
          {{ item.nodesProcessed + "/" + item.nodesTotal }}
        </td>
        <td class="font-weight-bold">
          <span :class="`text-uppercase ${getStatusColor(item.status)}--text`">
            {{ item.status }}
          </span>
        </td>
        <td class="text-center">
          <v-btn
            v-if="item.correlationId"
            icon
            @click.stop="copyToClipboard(item.correlationId)"
          >
            <app-icon>mdi-content-copy</app-icon>
          </v-btn>
        </td>
      </template>
    </data-grid>
  </v-col>
</template>

<script>
import AppIcon from "@/components/commons/icon/AppIcon"
import DataGrid from "@/components/commons/grid/DataGrid"
import FlashMessageMixin from "@/services/mixins/FlashMessageMixin"
import moment from "moment/moment"
import prettyMilliseconds from "pretty-ms"
import { API } from "@/api"
import { DATA_GRIDS } from "@/services/enums/dataGridEnums"
import { GRID } from "@/store/modules/grid/types"
import { OPERATOR } from "@/services/enums/gridEnums"
import { REQUESTS_STATE } from "@/store/modules/api/types"
import { internationalFormat } from "@/services/utils/dateFilters"
import { mapGetters } from "vuex"

export default {
  name: "TopologyProcesses",
  components: { AppIcon, DataGrid },
  mixins: [FlashMessageMixin],
  data() {
    return {
      DATA_GRIDS,
      headers: [
        {
          text: this.$t("grid.header.topologyName"),
          value: "topologyId",
          visible: true,
          align: "left",
        },
        {
          text: this.$t("grid.header.created"),
          value: "started",
          visible: true,
          align: "left",
        },
        {
          text: this.$t("grid.header.duration"),
          value: "duration",
          visible: true,
          align: "left",
        },
        {
          text: this.$t("grid.header.progress"),
          value: "progress",
          visible: true,
          align: "left",
        },
        {
          text: this.$t("grid.header.status"),
          value: "status",
          visible: true,
          align: "left",
        },
        {
          text: this.$t("grid.header.correlation_id"),
          value: "correlation_id",
          visible: true,
          align: "right",
          width: "150px",
        },
      ],
      quickFilters: [
        {
          name: "button.success",
          filter: [
            [
              {
                column: "status",
                operator: OPERATOR.EQUAL,
                value: ["SUCCESS"],
              },
            ],
          ],
        },
        {
          name: "button.inprogress",
          filter: [
            [
              {
                column: "status",
                operator: OPERATOR.EQUAL,
                value: ["IP"],
              },
            ],
          ],
        },
        {
          name: "button.failed",
          filter: [
            [
              {
                column: "status",
                operator: OPERATOR.EQUAL,
                value: ["FAILED"],
              },
            ],
          ],
        },
      ],
    }
  },
  computed: {
    ...mapGetters(DATA_GRIDS.DASHBOARD_PROCESSES, {
      sorterInitial: GRID.GETTERS.GET_SORTER,
      pagingInitial: GRID.GETTERS.GET_PAGING,
    }),
    ...mapGetters(REQUESTS_STATE.NAMESPACE, [REQUESTS_STATE.GETTERS.GET_STATE]),
    state() {
      return this[REQUESTS_STATE.GETTERS.GET_STATE](
        API.dashboard.getProcesses.id
      )
    },
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
      this.showFlashMessage(false, this.$t("flashMessages.topologyIdCopied"))
    },

    isInProgress(value) {
      return value.toLowerCase() === "in progress"
    },

    prettifyMilliseconds(milliseconds) {
      return this.prettyMs(milliseconds, { keepDecimalsOnWholeSeconds: true })
    },
  },
  filters: {
    internationalFormat,
  },
  async mounted() {
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

<style lang="scss" scoped>
tr {
  &:hover {
    cursor: pointer;
  }

  td {
    &:hover {
      cursor: default;
    }
  }
}
</style>
