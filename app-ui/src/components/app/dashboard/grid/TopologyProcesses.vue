<template>
  <v-col cols="12">
    <v-data-table
      :headers="headers"
      :items="items"
      :items-per-page="10"
      :loading="state.isSending"
      :hide-default-footer="true"
      class="elevation-3"
      item-key="id"
    >
      <template #item="{ item }">
        <tr>
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
            <span
              :class="`text-uppercase ${getStatusColor(item.status)}--text`"
            >
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
        </tr>
      </template>
    </v-data-table>
  </v-col>
</template>

<script>
import { internationalFormat } from "@/services/utils/dateFilters"
import moment from "moment/moment"
import prettyMilliseconds from "pretty-ms"
import AppIcon from "@/components/commons/icon/AppIcon"
import FlashMessageMixin from "@/services/mixins/FlashMessageMixin"

export default {
  name: "TopologyProcesses",
  props: {
    items: {
      type: Array,
      required: true,
    },
    state: {
      type: Object,
      required: true,
    },
    headers: {
      type: Array,
      required: true,
    },
  },
  mixins: [FlashMessageMixin],
  components: { AppIcon },
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
  filters: {
    internationalFormat,
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
