<template>
  <modal-template
    v-model="isOpen"
    :sending-title="$t('button.sending.deleting')"
    async
    :title="$t('users.delete.title')"
    :body="$t('users.delete.body')"
    :cancel-btn-text="$t('button.cancel')"
    :on-confirm="deleteAccount"
    :is-sending="state.isSending"
  >
    <template #default>
      <v-col cols="12">
        {{ $t('users.delete.body') }}
      </v-col>
    </template>
    <template #button>
      <v-btn class="ma-0" color="primary" icon @click="isOpen = !isOpen">
        <v-icon> delete </v-icon>
      </v-btn>
    </template>
    <template #sendingButton>
      <sending-button
        :sending-title="$t('button.sending.deleting')"
        :is-sending="state.isSending"
        :button-title="$t('button.delete')"
        :on-click="deleteAccount"
        :flat="false"
      />
    </template>
  </modal-template>
</template>

<script>
import { ADMIN_USERS } from '../../../../store/modules/adminUsers/types'
import { mapActions, mapGetters } from 'vuex'
import { REQUESTS_STATE } from '../../../../store/modules/api/types'
import { API } from '../../../../api'
import SendingButton from '@/components/commons/button/SendingButton'
import ModalTemplate from '@/components/commons/modal/ModalTemplate'

export default {
  name: 'DeleteUserHandler',
  components: { ModalTemplate, SendingButton },
  data() {
    return {
      isOpen: false,
    }
  },
  props: {
    userId: {
      type: String,
      required: true,
    },
  },
  computed: {
    ...mapGetters(REQUESTS_STATE.NAMESPACE, [REQUESTS_STATE.GETTERS.GET_STATE]),
    state() {
      return this[REQUESTS_STATE.GETTERS.GET_STATE]([API.admin.delete.id])
    },
  },
  methods: {
    ...mapActions(ADMIN_USERS.NAMESPACE, [ADMIN_USERS.ACTIONS.DELETE_USER_REQUEST]),
    async deleteAccount() {
      return await this[ADMIN_USERS.ACTIONS.DELETE_USER_REQUEST]({
        id: this.userId,
      })
    },
  },
}
</script>
