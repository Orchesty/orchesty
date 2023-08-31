<template>
  <modal-template
    v-model="isOpen"
    :title="$t('modal.header.userDetail')"
    :on-open="load"
  >
    <template #default>
      <v-col class="12">
        <user-form :user="user" readonly />
      </v-col>
    </template>
    <template #button>
      <v-btn icon @click="isOpen = !isOpen">
        <v-icon>search</v-icon>
      </v-btn>
    </template>
  </modal-template>
</template>

<script>
import { ADMIN_USERS } from "../../../../store/modules/adminUsers/types"
import { mapActions, mapGetters, mapState } from "vuex"
import { REQUESTS_STATE } from "../../../../store/modules/api/types"
import { API } from "../../../../api"
import UserForm from "../form/UserForm"
import ModalTemplate from "@/components/commons/modal/ModalTemplate"

export default {
  components: { ModalTemplate, UserForm },
  name: "UserDetailModal",
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
  },
  methods: {
    ...mapActions(ADMIN_USERS.NAMESPACE, [
      ADMIN_USERS.ACTIONS.GET_USER_REQUEST,
    ]),
    load() {
      this[ADMIN_USERS.ACTIONS.GET_USER_REQUEST]({ id: this.userId })
    },
  },
}
</script>
