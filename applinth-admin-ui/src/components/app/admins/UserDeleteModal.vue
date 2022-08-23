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
import { Admin } from "../../../types/gqlGeneratedPrivate";
import { EventBus } from "../../../enums";
import { TablesActions, TablesNamespaces } from "../../../store/modules/tables";
import { Action } from "vuex-class";
import { TableRefreshPayload } from "../../../types";

@Component({
  components: { ConfirmModal },
})
export default class UserDeleteModal extends Vue {
  EventBus = EventBus;
  isSending = false;

  @Action(TablesActions.Refresh, {
    namespace: TablesNamespaces.UsersTable,
  })
  refreshTable!: (payload: TableRefreshPayload) => Promise<void>;

  async onConfirm(payload: Admin) {
    this.isSending = true;
    // TODO call backend API
    // const result = await apiClient.callGraphqlPrivate<
    //   DeleteAdminMutation,
    //   DeleteAdminMutationVariables
    // >({
    //   ...api.users.deleteUser,
    //   variables: {
    //     id: payload.id,
    //   },
    // });
    // if (result.data) {
    //   alerts.addSuccessAlert("DELETE_ADMIN", "Smazáno");
    //   this.refreshTable({
    //     namespace: TablesNamespaces.UsersTable,
    //   });
    // }
    this.isSending = false;
  }
}
</script>
