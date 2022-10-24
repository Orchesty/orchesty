<template>
  <modal-template
    v-model="isOpen"
    :sending-title="$t('button.sending.editing')"
    async
    :title="$t('modal.header.updateUser')"
    :cancel-btn-text="$t('button.cancel')"
    :on-confirm="() => $refs.form.submit()"
    :is-sending="updateState.isSending"
    :on-open="load"
  >
    <template #default>
      <v-col cols="12">
        <user-form
          ref="form"
          :user="user"
          :groups="groups"
          :on-submit="onSubmit"
        />
      </v-col>
    </template>
    <template #sendingButton>
      <sending-button
        :sending-title="$t('button.sending.editing')"
        :is-sending="state.isSending"
        :button-title="$t('button.edit')"
        :on-click="() => $refs.form.submit()"
        :flat="false"
      />
    </template>
    <template #button>
      <v-btn color="primary" icon @click="isOpen = !isOpen">
        <v-icon>mdi-pencil-outline</v-icon>
      </v-btn>
    </template>
  </modal-template>
</template>

<script>
import { mapActions, mapGetters, mapState } from "vuex"
import { ADMIN_USERS } from "../../../../store/modules/adminUsers/types"
import { REQUESTS_STATE } from "../../../../store/modules/api/types"
import { API } from "../../../../api"
import UserForm from "../form/UserForm"
import SendingButton from "@/components/commons/button/AppButton"
import ModalTemplate from "@/components/commons/modal/ModalTemplate"

export default {
  components: { ModalTemplate, SendingButton, UserForm },
  name: "UserUpdateModal",
  data() {
    return {
      isOpen: false,
      groups: [],
    }
  },
  props: {
    userId: {
      type: String,
      required: true,
    },
  },
  computed: {
    ...mapState(ADMIN_USERS.NAMESPACE, ["user"]),
    ...mapGetters(REQUESTS_STATE.NAMESPACE, [REQUESTS_STATE.GETTERS.GET_STATE]),
    state() {
      return this[REQUESTS_STATE.GETTERS.GET_STATE]([API.admin.getById.id])
    },
    updateState() {
      return this[REQUESTS_STATE.GETTERS.GET_STATE]([API.admin.update.id])
    },
  },
  methods: {
    ...mapActions(ADMIN_USERS.NAMESPACE, [
      ADMIN_USERS.ACTIONS.GET_USER_REQUEST,
      ADMIN_USERS.ACTIONS.UPDATE_USER_REQUEST,
    ]),
    async onSubmit(values) {
      return await this[ADMIN_USERS.ACTIONS.UPDATE_USER_REQUEST]({
        id: this.userId,
        data: values,
        settings: this.user.settings,
      }).then((res) => {
        if (res) {
          this.isOpen = false
        }
      })
    },
    async load() {
      await this[ADMIN_USERS.ACTIONS.GET_USER_REQUEST]({ id: this.userId })
    },
  },
}
</script>
