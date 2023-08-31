<template>
  <data-grid
    v-if="topologyActive"
    ref="grid"
    :headers="headers"
    :is-loading="state.isSending"
    :namespace="DATA_GRIDS.USER_TASK"
    return-row-props
    show-active-row
    :simple-filter-enum="SIMPLE_FILTER.USER_TASK"
    simple-filter
    disable-filter
    item-key="id"
    show-select
    :quick-filters="quickFilters"
    content-enabled
    :fixed-filter="permanentFilter"
    two-column-layout
    @row-props="updateInfo"
    @input="onSelect"
    @reset="reset"
  >
    <template #content>
      <user-task-information :item="item" @reset="reset" @fetchGrid="fetchGrid" />
    </template>
    <template #default="{ items, isVisible }">
      <td v-if="isVisible('nodeName')" class="py-3 px-0 pointer text-start" @click="$refs.grid.onRowClicked(items)">
        <span>
          {{ items.item.nodeName }}
        </span>
      </td>
      <td v-if="isVisible('created')" class="pointer" @click="$refs.grid.onRowClicked(items)">
        <span>{{ items.item.created | internationalFormat }}</span>
      </td>
    </template>
    <template #groupActionButtons="contentEnabled">
      <v-row dense>
        <v-col cols="12" :lg="contentEnabled ? 4 : 12" class="py-0">
          <user-task-actions-modal
            text
            color="primary"
            :disabled="selected.length === 0"
            :selected="selected"
            type="acceptAll"
            :method="acceptAll"
            @reset="reset"
          />
          <user-task-actions-modal
            text
            color="error"
            :disabled="selected.length === 0"
            :selected="selected"
            type="rejectAll"
            :method="rejectAll"
            @reset="reset"
          />
        </v-col>
      </v-row>
    </template>
  </data-grid>
</template>

<script>
import { DATA_GRIDS } from '@/services/enums/dataGridEnums'
import DataGrid from '@/components/commons/grid/DataGrid'
import { mapActions, mapGetters } from 'vuex'
import { REQUESTS_STATE } from '@/store/modules/api/types'
import { API } from '@/api'
import { internationalFormat } from '@/services/utils/dateFilters'
import UserTaskInformation from '@/components/app/userTasks/grid/UserTaskInformation'
import { TOPOLOGIES } from '@/store/modules/topologies/types'
import { USER_TASKS } from '@/store/modules/userTasks/types'
import UserTaskActionsModal from '@/components/app/userTasks/modal/UserTaskActionsModal'
import QuickFiltersMixin from '@/services/mixins/QuickFiltersMixin'
import { GRID } from '@/store/modules/grid/types'
import { SIMPLE_FILTER } from '@/services/enums/dataGridFilterEnums'
export default {
  name: 'UserTaskTab',
  components: { UserTaskActionsModal, UserTaskInformation, DataGrid },
  mixins: [QuickFiltersMixin],
  data() {
    return {
      SIMPLE_FILTER,
      DATA_GRIDS,
      item: null,
      topologyID: null,
      selected: [],
      headers: [
        {
          text: 'topologies.userTask.headers.nodeName',
          value: 'nodeName',
          align: 'left',
          sortable: true,
          visible: true,
          class: 'pl-0',
          width: '50%',
        },
        {
          text: 'topologies.userTask.headers.created',
          value: 'created',
          align: 'left',
          sortable: true,
          visible: true,
          width: '50%',
        },
      ],
    }
  },
  computed: {
    ...mapGetters(DATA_GRIDS.USER_TASK, {
      sorterInitial: GRID.GETTERS.GET_SORTER,
      pagingInitial: GRID.GETTERS.GET_PAGING,
      gridFilter: GRID.GETTERS.GET_FILTER,
    }),
    ...mapGetters(REQUESTS_STATE.NAMESPACE, [REQUESTS_STATE.GETTERS.GET_STATE]),
    ...mapGetters(TOPOLOGIES.NAMESPACE, { topologyActive: TOPOLOGIES.GETTERS.GET_ACTIVE_TOPOLOGY }),
    state() {
      return this[REQUESTS_STATE.GETTERS.GET_STATE](API.userTask.grid.id)
    },
    permanentFilter() {
      return [
        [{ column: 'type', operator: 'EQ', value: ['userTask'], default: true }],
        [{ column: 'topologyId', operator: 'EQ', value: this.topologyActive._id, default: true }],
      ]
    },
  },
  filters: {
    internationalFormat,
  },
  methods: {
    ...mapActions(USER_TASKS.NAMESPACE, [
      USER_TASKS.ACTIONS.USER_TASK_REJECT_LIST,
      USER_TASKS.ACTIONS.USER_TASK_ACCEPT_LIST,
    ]),
    async acceptAll() {
      const response = await this[USER_TASKS.ACTIONS.USER_TASK_ACCEPT_LIST]({
        ids: this.selected.map((item) => item.id),
      })

      if (response) {
        await this.fetchGrid()
      }

      return response
    },
    async rejectAll() {
      const response = await this[USER_TASKS.ACTIONS.USER_TASK_REJECT_LIST]({
        ids: this.selected.map((item) => item.id),
      })

      if (response) {
        await this.fetchGrid()
      }

      return response
    },

    async fetchGrid() {
      await this.$refs.grid.fetchGrid(null, null, this.gridFilter, null, null)
    },
    async updateInfo(item) {
      this.item = item.item
      await this.$router.push({ path: `/topology/${this.$route.params.id}/userTask/${item.item.id}` })
    },
    onSelect(items) {
      this.selected = items
    },
    reset() {
      this.$refs.grid.activeIndex = null
      this.$refs.grid.clearSelected()
      this.selected = []
      this.item = null
    },
  },
  async mounted() {
    this.init('updated')
    await this.$refs.grid.fetchGridWithInitials(null, null, null, this.pagingInitial, this.sorterInitial)
  },
}
</script>
<style></style>
