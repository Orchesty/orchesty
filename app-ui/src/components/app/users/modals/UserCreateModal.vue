<template>
  <modal-template v-model="isOpen" :title="$t('modal.header.createUser')" :on-confirm="() => $refs.form.submit()">
    <template #default>
      <v-col cols="12">
        <user-form ref="form" :on-submit="submit" :is-sending="false" />
      </v-col>
    </template>
    <template #sendingButton>
      <sending-button
        :sending-title="$t('button.sending.create')"
        :is-sending="state.isSending"
        :button-title="$t('button.create')"
        :on-click="() => $refs.form.submit()"
        :flat="false"
      />
    </template>

    <template #button>
      <v-btn color="primary" @click="props.toggle">
        {{ $t('button.create') }}
      </v-btn>
    </template>
  </modal-template>
</template>

<script>
import UserForm from '../form/UserForm'
import { mapActions, mapGetters } from 'vuex'
import { REQUESTS_STATE } from '../../../../store/modules/api/types'
import { ADMIN_USERS } from '../../../../store/modules/adminUsers/types'
import { API } from '../../../../api'
import SendingButton from '@/components/commons/button/AppButton'
import ModalTemplate from '@/components/commons/modal/ModalTemplate'

export default {
  components: { ModalTemplate, SendingButton, UserForm },
  name: 'UserCreateModal',
  data() {
    return {
      isOpen: false,
    }
  },
  computed: {
    ...mapGetters(REQUESTS_STATE.NAMESPACE, [REQUESTS_STATE.GETTERS.GET_STATE]),
    state() {
      return this[REQUESTS_STATE.GETTERS.GET_STATE]([API.admin.create.id])
    },
  },
  methods: {
    ...mapActions(ADMIN_USERS.NAMESPACE, [ADMIN_USERS.ACTIONS.CREATE_USER_REQUEST]),
    async submit(values) {
      await this[ADMIN_USERS.ACTIONS.CREATE_USER_REQUEST](values).then((res) => {
        if (res) {
          this.isOpen = false
        }
      })
    },
  },
}
</script>
