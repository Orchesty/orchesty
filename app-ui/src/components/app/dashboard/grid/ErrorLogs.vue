<template>
  <v-col cols="12">
    <v-data-table
      :headers="headers"
      :items="items"
      :items-per-page="10"
      :loading="state.isSending"
      :hide-default-footer="true"
      class="elevation-3"
    >
      <template #item="{ item }">
        <tr @click="logRedirect(item)">
          <td>
            <span>{{ item.time | internationalFormat }}</span>
          </td>
          <td>
            <span>{{ item.topologyName }}</span>
          </td>
          <td class="py-0 truncate">
            <span>{{ item.message }}</span>
          </td>
          <td>
            <span
              :class="`font-weight-bold ${setColor(item.level)}--text`"
              class="text-uppercase"
              >{{ item.level }}</span
            >
          </td>
        </tr>
      </template>
    </v-data-table>
  </v-col>
</template>

<script>
import { ROUTES } from "@/services/enums/routerEnums"
import { internationalFormat } from "@/services/utils/dateFilters"

export default {
  name: "ErrorLogs",
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
  methods: {
    async logRedirect(item) {
      if (item.topologyId !== "System") {
        await this.$router.push({
          name: ROUTES.TOPOLOGY.LOGS,
          params: { id: item.topologyId },
        })
      } else {
        await this.$router.push({ name: ROUTES.LOGS, params: { item: item } })
      }
    },
    setColor(item) {
      if (item.toLowerCase() === "error") {
        return "error"
      }
      if (item.toLowerCase() === "warning") {
        return "warning"
      }
      if (item.toLowerCase() === "info") {
        return "info"
      }
      return "black"
    },
  },
  filters: {
    internationalFormat,
  },
}
</script>
