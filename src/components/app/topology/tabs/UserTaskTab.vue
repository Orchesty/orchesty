<template>
  <data-grid
    v-if="topologyID"
    ref="grid"
    :headers="headers"
    disable-search
    :is-loading="state.isSending"
    :namespace="DATA_GRIDS.USER_TASK"
    return-row-props
    show-active-row
    item-key="id"
    show-select
    :title="$t('topologies.userTask.title')"
    :quick-filters="quickFilters"
    :permanent-filter="permanentFilter"
    :content-enabled="true"
    fill-height
    :request-params="{ id: topologyID }"
    disabled-advanced-filter
    is-user-task
    @row-props="updateInfo"
    @input="onSelect"
    @reset="reset"
  >
    <template #content>
      <user-task-information :item="item" @reset="reset" />
    </template>
    <template #default="{ items, isVisible }">
      <td v-if="isVisible('nodeName')" class="py-3 px-0 pointer text-start" @click="$refs.grid.onRowClicked(items)">
        <span>
          {{ items.item.nodeName }}
        </span>
      </td>
      <td v-if="isVisible('updated')" class="pointer" @click="$refs.grid.onRowClicked(items)">
        <span>{{ items.item.updated | internationalFormat }}</span>
      </td>
    </template>
    <template #groupActionButtons="contentEnabled">
      <v-row>
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
import DataGrid from '@/components/commons/table/DataGrid'
import { mapActions, mapGetters, mapState } from 'vuex'
import { REQUESTS_STATE } from '@/store/modules/api/types'
import { API } from '@/api'
import { internationalFormat } from '@/services/utils/dateFilters'
import UserTaskInformation from '@/components/app/userTasks/grid/UserTaskInformation'
import { TOPOLOGIES } from '@/store/modules/topologies/types'
import { USER_TASKS } from '@/store/modules/userTasks/types'
import UserTaskActionsModal from '@/components/app/userTasks/modal/UserTaskActionsModal'
import QuickFiltersMixin from '@/components/commons/mixins/QuickFiltersMixin'
export default {
  name: 'UserTaskTab',
  components: { UserTaskActionsModal, UserTaskInformation, DataGrid },
  mixins: [QuickFiltersMixin],
  data() {
    return {
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
  computed: {
    ...mapState(TOPOLOGIES.NAMESPACE, ['topology']),
    ...mapGetters(REQUESTS_STATE.NAMESPACE, [REQUESTS_STATE.GETTERS.GET_STATE]),
    state() {
      return this[REQUESTS_STATE.GETTERS.GET_STATE](API.userTask.grid.id)
    },
    permanentFilter() {
      return [
        [{ column: 'type', operator: 'EQ', value: ['userTask'], default: true }],
        [{ column: 'topologyId', operator: 'EQ', value: this.topologyID, default: true }],
      ]
    },
  },
  filters: {
    internationalFormat,
  },
  watch: {
    topology(value) {
      this.topologyID = value._id
    },
  },
  mounted() {
    if (this.topology) {
      this.topologyID = this.topology._id
    }
    if (this.$route.params.userTaskId) {
      this.item = { id: this.$route.params.userTaskId }
    }
    this.init('updated')
  },
  methods: {
    ...mapActions(USER_TASKS.NAMESPACE, [
      USER_TASKS.ACTIONS.USER_TASK_REJECT_LIST,
      USER_TASKS.ACTIONS.USER_TASK_ACCEPT_LIST,
    ]),
    async acceptAll() {
      return await this[USER_TASKS.ACTIONS.USER_TASK_ACCEPT_LIST]({
        ids: this.selected.map((item) => item.id),
        topologyID: this.topologyID,
      })
    },
    async rejectAll() {
      return await this[USER_TASKS.ACTIONS.USER_TASK_REJECT_LIST]({
        ids: this.selected.map((item) => item.id),
        topologyID: this.topologyID,
      })
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
}
</script>
<style></style>
