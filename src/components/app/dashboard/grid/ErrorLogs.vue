<template>
  <v-col cols="12">
    <v-data-table
      :headers="headers"
      :items="items"
      :items-per-page="10"
      :loading="state.isSending"
      :hide-default-footer="true"
      class="elevation-3 dashboard-table"
    >
      <template #item="{ item }">
        <tr @click="logRedirect(item)">
          <td>
            <span>{{ item.time | internationalFormat }}</span>
          </td>
          <td class="text-center">
            <span>{{ item.topologyName }}</span>
          </td>
          <td class="py-0 text-start truncate">
            <span>{{ item.message }}</span>
          </td>
          <td class="text-center">
            <span :class="`subtitle-2 font-weight-bold ${setColor(item.level)}--text`">{{ item.level }}</span>
          </td>
        </tr>
      </template>
      <template #top>
        <div class="bg-primary">
          <h5 class="pl-3 py-3">
            {{ $t('topologies.dashboard.tables.errorLogs') }}
          </h5>
        </div>
      </template>
    </v-data-table>
  </v-col>
</template>

<script>
import { ROUTES } from '@/router/routes'
import { internationalFormat } from '@/filters'

export default {
  name: 'ErrorLogs',
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
      if (item.topologyId !== 'System') {
        await this.$router.push({ name: ROUTES.TOPOLOGIES.LOGS, params: { id: item.topologyId } })
      } else {
        await this.$router.push({ name: ROUTES.LOGS, params: { item: item } })
      }
    },
    setColor(item) {
      if (item.toLowerCase() === 'error') {
        return 'error'
      }
      if (item.toLowerCase() === 'warning') {
        return 'warning'
      }
      if (item.toLowerCase() === 'info') {
        return 'info'
      }
      return 'black'
    },
  },
  filters: {
    internationalFormat,
  },
}
</script>
