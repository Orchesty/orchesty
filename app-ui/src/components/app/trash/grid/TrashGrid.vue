<template>
  <data-grid
    ref="grid"
    :headers="headers"
    disable-search
    disabled-advanced-filter
    :is-loading="state.isSending"
    :namespace="DATA_GRIDS.TRASH"
    :simple-filter-enum="SIMPLE_FILTER.TRASH"
    return-row-props
    show-active-row
    item-key="id"
    show-select
    :quick-filters="quickFilters"
    :content-enabled="true"
    fill-height
    is-user-task
    simple-filter
    @row-props="updateInfo"
    @input="onSelect"
  >
    <template #content>
      <user-task-information :item="item" :is-trash="true" @reset="reset" />
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
      <td v-if="isVisible('updated')" class="pointer text-center" @click="$refs.grid.onRowClicked(items)">
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
import DataGrid from '@/components/commons/table/DataGrid'
import { mapActions, mapGetters, mapState } from 'vuex'
import { REQUESTS_STATE } from '@/store/modules/api/types'
import { API } from '@/api'
import { internationalFormat } from '@/services/utils/dateFilters'
import UserTaskInformation from '@/components/app/userTasks/grid/UserTaskInformation'
import { OPERATOR } from '@/services/enums/gridEnums'
import { TOPOLOGIES } from '@/store/modules/topologies/types'
import UserTaskActionsModal from '@/components/app/userTasks/modal/UserTaskActionsModal'
import { TRASH } from '@/store/modules/trash/types'
import { ROUTES } from '@/services/enums/routerEnums'
import QuickFiltersMixin from '@/components/commons/mixins/QuickFiltersMixin'
import { SIMPLE_FILTER } from '@/services/enums/dataGridFilterEnums'
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
          text: 'topologies.userTask.headers.nodeNameTopology',
          value: 'nodeName',
          align: 'left',
          sortable: true,
          visible: true,
          class: 'pl-0',
          width: '50%',
        },
        {
          text: 'topologies.userTask.headers.updated',
          value: 'updated',
          align: 'center',
          sortable: true,
          visible: true,
          width: '50%',
        },
      ],
    }
  },
  props: {
    filter: {
      type: Object,
      default: () => ({}),
    },
    native: {
      type: String,
      default: null,
    },
  },
  computed: {
    ...mapState(TOPOLOGIES.NAMESPACE, ['topology']),
    ...mapGetters(REQUESTS_STATE.NAMESPACE, [REQUESTS_STATE.GETTERS.GET_STATE]),
    state() {
      return this[REQUESTS_STATE.GETTERS.GET_STATE](API.trash.grid.id)
    },
  },
  filters: {
    internationalFormat,
  },
  watch: {
    topology(value) {
      this.topologyID = value._id
    },
    filter: {
      deep: true,
      handler(val) {
        let filter = [[{ column: 'type', operator: 'EQ', value: ['trash'] }]]
        let keys = Object.keys(val).filter((key) => {
          return val[key]
        })
        keys.forEach((key) => {
          filter.push([
            { column: key, operator: OPERATOR.LIKE, value: val[key] },
            { column: key.replace('Name', 'Id'), operator: OPERATOR.LIKE, value: val[key] },
          ])
        })
        this.filterGrid = filter
      },
    },
  },
  mounted() {
    if (this.topology) {
      this.topologyID = this.topology._id
    }
    if (this.$route.params.trashId) {
      this.item = { id: this.$route.params.trashId }
    }
    this.init('updated')
  },
  methods: {
    ...mapActions(TRASH.NAMESPACE, [TRASH.ACTIONS.TRASH_ACCEPT_LIST, TRASH.ACTIONS.TRASH_REJECT_LIST]),
    sendFilter() {
      this.$refs.grid.fetchGridWithFilter(this.filterGrid, null, this.native)
    },
    async acceptAll() {
      return await this[TRASH.ACTIONS.TRASH_ACCEPT_LIST]({ ids: this.selected.map((item) => item.id) })
    },
    async rejectAll() {
      return await this[TRASH.ACTIONS.TRASH_REJECT_LIST]({ ids: this.selected.map((item) => item.id) })
    },
    async updateInfo(item) {
      this.item = item.item
      await this.$router.push({ name: ROUTES.TRASH_DETAIL, params: { trashId: item.item.id } })
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
}
</script>
<style></style>