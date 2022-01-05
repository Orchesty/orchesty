<template>
  <data-grid :headers="headers" :is-loading="state.isSending" :namespace="DATA_GRIDS.USER_TASK" disable-filter>
    <template #default="{ items, isVisible }">
      <td v-if="isVisible('key')">{{ items }}</td>
      <td v-if="isVisible('value')">{{ items }}</td>
    </template>
  </data-grid>
</template>
<script>
import { DATA_GRIDS } from '../../../../store/grid/grids'
import DataGrid from '../../../commons/table/DataGrid'
import { REQUESTS_STATE } from '../../../../store/modules/api/types'
import { API } from '../../../../api'
import { mapGetters } from 'vuex'
export default {
  name: 'UserTaskGridHandler',
  components: { DataGrid },
  computed: {
    ...mapGetters(REQUESTS_STATE.NAMESPACE, [REQUESTS_STATE.GETTERS.GET_STATE]),
    state() {
      return this[REQUESTS_STATE.GETTERS.GET_STATE]([API.userTask.list.id])
    },
  },
  data() {
    return {
      DATA_GRIDS,
      headers: [
        {
          text: 'userTask.grid.node',
          value: 'key',
          align: 'left',
          sortable: true,
          visible: true,
        },
        {
          text: 'userTask.grid.created',
          value: 'key',
          align: 'left',
          sortable: true,
          visible: true,
        },
        {
          text: 'userTask.grid.data',
          value: 'value',
          align: 'left',
          sortable: true,
          visible: true,
        },
        {
          text: 'userTask.grid.logs',
          value: 'value',
          align: 'left',
          sortable: true,
          visible: true,
        },
        {
          text: 'userTask.grid.actions',
          value: 'value',
          align: 'left',
          sortable: true,
          visible: true,
        },
      ],
    }
  },
}
</script>

<style scoped></style>
