<template>
  <div>
    <DataGrid
      ref="gridLogs"
      :headers="headers"
      :grid-settings="GRIDS.LOGS"
      :sort-by="['timestamp']"
      :sort-desc="[true]"
      item-key="timestamp"
      show-expand
    >
      <template #expand="{ items }">
        <span class="error--text">{{ items.item.message }}</span>
      </template>
      <template #default="{ items, expanded }">
        <td :style="expanded ? 'border-bottom: none' : ''">
          {{ toLocalDateTime(items.item.timestamp) }}
        </td>
        <td :style="expanded ? 'border-bottom: none' : ''">
          {{ items.item.topology_name }}
        </td>
        <td :style="expanded ? 'border-bottom: none' : ''">
          {{ items.item.node_id }}
        </td>
        <td :style="expanded ? 'border-bottom: none' : ''">
          {{ items.item.node_name }}
        </td>
        <td :style="expanded ? 'border-bottom: none' : ''">
          <span
            :class="`font-weight-bold ${setColor(
              items.item.severity
            )}--text text-uppercase`"
          >
            {{ items.item.severity }}
          </span>
        </td>
        <Tooltip>
          <template #activator="{ on, attrs }">
            <td
              v-bind="attrs"
              :style="expanded ? 'border-bottom: none' : ''"
              class="text-end"
              v-on="on"
            >
              <BaseButton
                v-if="items.item.correlation_id"
                icon
                :on-click="() => copyToClipboard(items.item.correlation_id)"
              >
                <template #icon>
                  <v-icon>mdi-content-copy</v-icon>
                </template>
              </BaseButton>
            </td>
          </template>
          <template #tooltip>
            {{
              items.item.correlation_id
                ? items.item.correlation_id
                : $t("logsPage.item.systemLog")
            }}
          </template>
        </Tooltip>
      </template>
    </DataGrid>
  </div>
</template>

<script>
import { GRIDS } from "@/utils/gridsConfig"
import {
  toLocalDateTime,
  toLocalTime,
} from "@/localization/filters/dateFilters"
import DataGrid from "@/components/commons/DataGrid"
import BaseButton from "@/components/commons/BaseButton"
import Tooltip from "@/components/commons/Tooltip"
import showFlashMessage from "@/utils/flashMessage"
import { FLASH_MESSAGES_TYPES } from "@/store/flashMessages/types"

export default {
  name: "LogsPage",
  components: { BaseButton, DataGrid, Tooltip },
  data() {
    return {
      headers: [
        {
          text: this.$t("grid.logs.header.timestamp"),
          value: "timestamp",
          align: "start",
        },
        {
          text: this.$t("grid.logs.header.topologyName"),
          value: "topology_name",
          sortable: false,
        },
        {
          text: this.$t("grid.logs.header.nodeId"),
          value: "node_id",
          align: "start",
        },
        {
          text: this.$t("grid.logs.header.nodeName"),
          value: "node_name",
          align: "start",
          sortable: false,
        },
        {
          text: this.$t("grid.logs.header.severity"),
          value: "severity",
          align: "start",
        },
        {
          text: this.$t("grid.logs.header.correlationId"),
          value: "correlation_id",
          align: "right",
        },
      ],
      GRIDS,
      toLocalDateTime,
      toLocalTime,
    }
  },
  methods: {
    setColor(props) {
      if (props.toLowerCase() === "error") {
        return "error"
      }
      if (props.toLowerCase() === "warning") {
        return "warning"
      }
      if (props.toLowerCase() === "ok") {
        return "info"
      }
      return "black"
    },
    copyToClipboard(correlationId) {
      navigator.clipboard.writeText(correlationId)
      showFlashMessage(
        this.$t("flashMessage.idCopied"),
        FLASH_MESSAGES_TYPES.SUCCESS
      )
    },
  },
}
</script>
