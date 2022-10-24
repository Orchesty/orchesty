<template>
  <data-grid
    :headers="headers"
    :is-loading="state.isSending"
    :namespace="DATA_GRIDS.ADMIN_USERS_LIST"
    disable-filter
  >
    <template slot="toolbar">
      <user-create-modal />
    </template>
    <template #default="{ items, isVisible }">
      <td v-if="isVisible('name')">{{ items.item.id }}</td>
      <td v-if="isVisible('email')">{{ items.item.email }}</td>
      <td v-if="isVisible('action')" class="text-center text-no-wrap">
        <user-update-modal :user-id="items.item.id" />
        <user-delete-modal :user-id="items.item.id" />
      </td>
    </template>
  </data-grid>
</template>

<script>
import DataGrid from "../../../commons/grid/DataGrid"
import { DATA_GRIDS } from "@/services/enums/dataGridEnums"
import { mapGetters } from "vuex"
import { REQUESTS_STATE } from "../../../../store/modules/api/types"
import { API } from "../../../../api"
import { toLocalDateTime } from "../../../../services/utils/dateFilters"
import UserCreateModal from "../modals/UserCreateModal"
import UserDeleteModal from "../modals/UserDeleteModal"
import UserUpdateModal from "../modals/UserUpdateModal"

export default {
  name: "UserGrid",
  components: {
    UserCreateModal,
    UserUpdateModal,
    UserDeleteModal,
    DataGrid,
  },
  computed: {
    ...mapGetters(REQUESTS_STATE.NAMESPACE, [REQUESTS_STATE.GETTERS.GET_STATE]),
    state() {
      return this[REQUESTS_STATE.GETTERS.GET_STATE]([API.admin.getList.id])
    },
  },
  data() {
    return {
      DATA_GRIDS,
      headers: [
        {
          text: this.$t("grid.headers.id"),
          value: "name",
          align: "left",
          sortable: true,
          visible: true,
          width: "300px",
        },
        {
          text: this.$t("grid.headers.email"),
          value: "email",
          align: "left",
          sortable: true,
          visible: true,
          width: "300px",
        },
        {
          text: this.$t("grid.headers.actions"),
          value: "action",
          align: "right",
          sortable: false,
          alwaysVisible: true,
          width: "150px",
        },
      ],
    }
  },
  filters: {
    toLocalDateTime,
  },
}
</script>
