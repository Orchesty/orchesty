<template>
  <data-grid
    ref="grid"
    :headers="headers"
    :is-loading="state.isSending"
    :namespace="DATA_GRIDS.IMPLEMENTATIONS_LIST"
    disable-filter
    disable-search
    disable-pagination
  >
    <template #default="{ items, isVisible }">
      <td v-if="isVisible('name')" class="text-start">{{ items.item.name }}</td>
      <td v-if="isVisible('url')" class="text-start">{{ items.item.url }}</td>
      <td class="text-end">
        <implementation-update-modal :item-id="items.item.id" />
        <implementation-delete-modal :item-id="items.item.id" />
      </td>
    </template>
  </data-grid>
</template>

<script>
import { DATA_GRIDS } from '@/services/enums/dataGridEnums'
import DataGrid from '../../../commons/grid/DataGrid'
import { REQUESTS_STATE } from '@/store/modules/api/types'
import { API } from '@/api'
import { mapGetters } from 'vuex'
import ImplementationUpdateModal from '@/components/app/implementations/modal/ImplementationUpdateModal'
import ImplementationDeleteModal from '@/components/app/implementations/modal/ImplementationDeleteModal'
import { GRID } from '@/store/modules/grid/types'
export default {
  name: 'ImplementationGrid',
  components: { ImplementationDeleteModal, ImplementationUpdateModal, DataGrid },
  computed: {
    ...mapGetters(DATA_GRIDS.SCHEDULED_TASK, {
      sorterInitial: GRID.GETTERS.GET_SORTER,
      pagingInitial: GRID.GETTERS.GET_PAGING,
    }),
    ...mapGetters(REQUESTS_STATE.NAMESPACE, [REQUESTS_STATE.GETTERS.GET_STATE]),
    state() {
      return this[REQUESTS_STATE.GETTERS.GET_STATE](API.implementation.grid.id)
    },
  },
  data() {
    return {
      DATA_GRIDS,
      headers: [
        {
          text: this.$t('grid.header.name'),
          value: 'name',
          align: 'left',
          sortable: true,
          visible: true,
          width: '300px',
        },
        {
          text: this.$t('grid.header.url'),
          value: 'url',
          align: 'left',
          sortable: true,
          visible: true,
          width: '300px',
        },
        {
          text: this.$t('grid.header.actions'),
          value: 'actions',
          align: 'right',
          sortable: true,
          visible: true,
          width: '160px',
        },
      ],
    }
  },
  async mounted() {
    await this.$refs.grid.fetchGridWithInitials(null, null, null, this.pagingInitial, this.sorterInitial)
  },
}
</script>
