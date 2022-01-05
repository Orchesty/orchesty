<template>
  <data-grid
    :headers="headers"
    :is-loading="state.isSending"
    :namespace="DATA_GRIDS.IMPLEMENTATIONS_LIST"
    disable-filter
    disable-toolbar
    disable-search
    disable-pagination
  >
    <template slot="toolbar">
      <implementation-create-modal />
    </template>
    <template #default="{ items, isVisible }">
      <td v-if="isVisible('name')">{{ items.item.name }}</td>
      <td v-if="isVisible('url')">{{ items.item.url }}</td>
      <td>
        <implementation-update-modal :item-id="items.item.id" />
        <implementation-delete-modal :item-id="items.item.id" />
      </td>
    </template>
  </data-grid>
</template>

<script>
import { DATA_GRIDS } from '@/services/enums/dataGridEnums'
import DataGrid from '../../../commons/table/DataGrid'
import { REQUESTS_STATE } from '../../../../store/modules/api/types'
import { API } from '../../../../api'
import { mapGetters } from 'vuex'
import ImplementationCreateModal from '@/components/app/implementations/modal/ImplementationCreateModal'
import ImplementationUpdateModal from '@/components/app/implementations/modal/ImplementationUpdateModal'
import ImplementationDeleteModal from '@/components/app/implementations/modal/ImplementationDeleteModal'
export default {
  name: 'ImplementationGrid',
  components: { ImplementationDeleteModal, ImplementationUpdateModal, ImplementationCreateModal, DataGrid },
  computed: {
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
          text: 'implementation.form.name.label',
          value: 'name',
          align: 'left',
          sortable: true,
          visible: true,
          width: '300px',
        },
        {
          text: 'implementation.form.url.label',
          value: 'url',
          align: 'center',
          sortable: true,
          visible: true,
          width: '300px',
        },
        {
          text: 'implementation.form.action.label',
          value: 'actions',
          align: 'center',
          sortable: true,
          visible: true,
          width: '160px',
        },
      ],
    }
  },
}
</script>
