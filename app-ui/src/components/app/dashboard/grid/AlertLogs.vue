<template>
  <v-col cols="12" xl="6">
    <v-data-table
      :headers="headers"
      :items="items"
      :items-per-page="10"
      :loading="state.isSending"
      class="elevation-3 dashboard-table"
    >
      <template #item="{ item }">
        <tr>
          <td>
            <span>{{ item.time }}</span>
          </td>
          <td>
            <span>{{ item.topologyName }}</span>
          </td>
          <td @click="$emit('logRedirect', item)">
            <span>{{ item.topologyId }}</span>
          </td>
          <td>
            <span>{{ item.level }}</span>
          </td>
        </tr>
      </template>
      <template #top>
        <div class="bg-primary">
          <h3 class="pl-3 py-3">
            {{ $t("page.heading.alertLogs") }}
          </h3>
        </div>
      </template>
    </v-data-table>
  </v-col>
</template>

<script>
export default {
  name: "AlertLogs",
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
}
</script>

<style scoped></style>
