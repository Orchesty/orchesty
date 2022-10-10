<template>
  <data-grid
    ref="grid"
    :headers="headers"
    :is-loading="state.isSending"
    :namespace="DATA_GRIDS.TRASH"
    :simple-filter-enum="SIMPLE_FILTER.TRASH"
    return-row-props
    show-active-row
    item-key="id"
    show-select
    :quick-filters="quickFilters"
    :fixed-filter="[[{ column: 'type', operator: 'EQ', value: ['trash'] }]]"
    :content-enabled="true"
    two-column-layout
    simple-filter
    @row-props="updateInfo"
    @input="onSelect"
  >
    <template #content>
      <user-task-information :item="item" :is-trash="true" @reset="reset" @fetchGrid="fetchGrid" />
    </template>
    <template #default="{ items, isVisible }">
      <td
        v-if="isVisible('nodeName')"
        class="py-3 px-0 pointer text-start truncate"
        @click="$refs.grid.onRowClicked(items)"
      >
        <span>{{ items.item.topologyName }}</span>
        <br />
        <span>{{ items.item.nodeName }}</span>
      </td>
      <td v-if="isVisible('updated')" class="pointer" @click="$refs.grid.onRowClicked(items)">
        <span>{{ items.item.updated | internationalFormat }}</span>
      </td>
    </template>
    <template #groupActionButtons="contentEnabled">
      <v-row dense>
        <v-col cols="12" :lg="contentEnabled ? 4 : 12" class="py-0">
          <user-task-actions-modal
            text
            color="success"
            :disabled="selected.length === 0"
            :selected="selected"
            type="acceptAll"
            :on-submit="acceptAll"
            @reset="reset"
          />
          <user-task-actions-modal
            text
            color="error"
            :disabled="selected.length === 0"
            :selected="selected"
            type="rejectAll"
            :on-submit="rejectAll"
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
import UserTaskActionsModal from '@/components/app/userTasks/modal/UserTaskActionsModal'
import { TRASH } from '@/store/modules/trash/types'
import { ROUTES } from '@/services/enums/routerEnums'
import QuickFiltersMixin from '@/services/mixins/QuickFiltersMixin'
import { SIMPLE_FILTER } from '@/services/enums/dataGridFilterEnums'
import { GRID } from '@/store/modules/grid/types'
import { redirectTo } from '@/services/utils/utils'

export default {
  name: 'TrashGrid',
  components: { UserTaskActionsModal, UserTaskInformation, DataGrid },
  mixins: [QuickFiltersMixin],
  data() {
    return {
      SIMPLE_FILTER,
      DATA_GRIDS,
      item: null,
      topologyID: null,
      selected: [],
      filterGrid: [],
      headers: [
        {
          text: this.$t('topologies.userTask.headers.nodeNameTopology'),
          value: 'nodeName',
          align: 'left',
          sortable: true,
          visible: true,
          class: 'pl-0',
          width: '50%',
        },
        {
          text: this.$t('topologies.userTask.headers.updated'),
          value: 'updated',
          align: 'left',
          sortable: true,
          visible: true,
          width: '50%',
        },
      ],
    }
  },
  computed: {
    ...mapGetters(REQUESTS_STATE.NAMESPACE, [REQUESTS_STATE.GETTERS.GET_STATE]),
    ...mapGetters(DATA_GRIDS.TRASH, {
      activeSorter: GRID.GETTERS.GET_SORTER,
      activePaging: GRID.GETTERS.GET_PAGING,
      activeFilter: GRID.GETTERS.GET_FILTER,
    }),
    state() {
      return this[REQUESTS_STATE.GETTERS.GET_STATE](API.trash.grid.id)
    },
  },
  filters: {
    internationalFormat,
  },
  methods: {
    ...mapActions(TRASH.NAMESPACE, [TRASH.ACTIONS.TRASH_ACCEPT_LIST, TRASH.ACTIONS.TRASH_REJECT_LIST]),
    async acceptAll() {
      const response = await this[TRASH.ACTIONS.TRASH_ACCEPT_LIST]({ ids: this.selected.map((item) => item.id) })
      if (response) await this.fetchGrid()

      this.selected = []
      return response
    },
    async rejectAll() {
      const response = await this[TRASH.ACTIONS.TRASH_REJECT_LIST]({ ids: this.selected.map((item) => item.id) })
      if (response) await this.fetchGrid()

      this.selected = []
      return response
    },
    async fetchGrid() {
      await this.$refs.grid.fetchGrid(null, null, this.activeFilter, this.activePaging, this.activeSorter)
    },
    async updateInfo(item) {
      this.item = item.item
      await redirectTo(this.$router, { name: ROUTES.TRASH_DETAIL, params: { trashId: item.item.id } })
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
    await this.$refs.grid.fetchGridWithInitials(null, null, null, this.pagingInitial, this.sorterInitial)
    this.init('updated')

    if (this.$route.params?.trashId) this.updateInfo({ item: { id: this.$route.params.trashId } })
  },
}
</script>
<style></style>
