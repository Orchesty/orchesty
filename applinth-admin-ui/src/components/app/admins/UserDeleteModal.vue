<template>
  <ConfirmModal
    :title="$t('usersPage.removeUser') + '?'"
    :on-confirm="onConfirm"
    :event-bus-name="EventBus.UserDeleteModal"
    :is-sending="isSending"
  />
</template>

<script lang="ts">
import { Component, Vue } from "vue-property-decorator"
import ConfirmModal from "../../commons/layouts/ConfirmModal.vue"
import { EventBus } from "../../../enums"
import { alerts, callApi } from "@/utils"
import { OutputUser, UsersDeleteRequest } from "@/api/generated"
import { api } from "@/api"
import { eventBus } from "@/utils/eventBus"

@Component({
  components: { ConfirmModal },
})
export default class UserDeleteModal extends Vue {
  EventBus = EventBus
  isSending = false

  async onConfirm(payload: OutputUser) {
    this.isSending = true
    const res = await callApi<UsersDeleteRequest>(api.users.delete, {
      uid: payload.uid as string,
    })

    if (res?.msg) {
      alerts.addSuccessAlert(
        "DELETE_ADMIN",
        this.$t("message.deleted") as string
      )

      eventBus.$emit(EventBus.UsersRefreshList)
    }
    this.isSending = false
  }
}
</script>
