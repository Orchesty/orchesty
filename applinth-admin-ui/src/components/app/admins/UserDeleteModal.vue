<template>
  <ConfirmModal
    title="Smazat uživatele?"
    :on-confirm="onConfirm"
    :event-bus-name="EventBus.UserDeleteModal"
    :is-sending="isSending"
  />
</template>

<script lang="ts">
import { Component, Vue } from "vue-property-decorator";
import ConfirmModal from "../../commons/ConfirmModal.vue";
import {
  DeleteAdminMutation,
  DeleteAdminMutationVariables,
  Admin,
} from "../../../types/gqlGeneratedPrivate";
import { api } from "../../../api";
import { EventBus } from "../../../enums";
import { apiClient } from "../../../utils/apiClient";
import { TablesActions, TablesNamespaces } from "../../../store/modules/tables";
import { alerts } from "../../../utils";
import { Action } from "vuex-class";
import { TableRefreshPayload } from "../../../types";

@Component({
  components: { ConfirmModal },
})
export default class UserDeleteModal extends Vue {
  EventBus = EventBus;
  isSending = false;

  @Action(TablesActions.Refresh, {
    namespace: TablesNamespaces.AdminsTable,
  })
  refreshTable!: (payload: TableRefreshPayload) => Promise<void>;

  async onConfirm(payload: Admin) {
    this.isSending = true;
    const result = await apiClient.callGraphqlPrivate<
      DeleteAdminMutation,
      DeleteAdminMutationVariables
    >({
      ...api.admins.deleteAdmin,
      variables: {
        id: payload.id,
      },
    });
    if (result.data) {
      alerts.addSuccessAlert("DELETE_ADMIN", "Smazáno");
      this.refreshTable({
        namespace: TablesNamespaces.AdminsTable,
      });
    }
    this.isSending = false;
  }
}
</script>
