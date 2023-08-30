<template>
  <modal-template
    v-model="isOpen"
    :title="$t('notifications.modal.title', [service ? NOTIFICATION_TYPES[service.type] : ''])"
    :on-close="() => $refs.form.initForm()"
  >
    <template #button>
      <v-btn icon @click="isOpen = !isOpen">
        <v-icon color="primary"> mdi-pencil-outline </v-icon>
      </v-btn>
    </template>
    <template #default>
      <v-row dense>
        <v-col cols="12">
          <curl-form
            v-if="service.type === NOTIFICATION_TYPES.CURL"
            ref="form"
            :on-submit="onSubmit"
            :service="service"
          />
          <amqp-form
            v-else-if="service.type === NOTIFICATION_TYPES.AMQP"
            ref="form"
            :on-submit="onSubmit"
            :service="service"
          />
          <email-sender
            v-else-if="service.type === NOTIFICATION_TYPES.EMAIL"
            ref="form"
            :on-submit="onSubmit"
            :service="service"
          />
        </v-col>
      </v-row>
    </template>
    <template #sendingButton>
      <v-row dense>
        <v-col cols="12" class="d-flex justify-end">
          <app-button
            :sending-title="$t('button.sending.editing')"
            :is-sending="state.isSending"
            :button-title="$t('button.edit')"
            :on-click="() => $refs.form.submit()"
            :flat="false"
          />
        </v-col>
      </v-row>
    </template>
  </modal-template>
</template>

<script>
import { NOTIFICATION_TYPES } from '@/services/enums/notificationsEnums'
import { mapActions, mapGetters } from 'vuex'
import { REQUESTS_STATE } from '../../../../store/modules/api/types'
import { API } from '../../../../api'
import CurlForm from '../form/CurlForm'
import AmqpForm from '../form/AmqpForm'
import EmailSender from '../form/EmailSender'
import { NOTIFICATIONS } from '../../../../store/modules/notifications/types'
import ModalTemplate from '@/components/commons/modal/ModalTemplate'
import AppButton from '@/components/commons/button/AppButton'
export default {
  name: 'NotificationUpdateModal',
  components: { AppButton, ModalTemplate, EmailSender, AmqpForm, CurlForm },
  data() {
    return {
      isOpen: false,
      NOTIFICATION_TYPES,
    }
  },
  computed: {
    ...mapGetters(REQUESTS_STATE.NAMESPACE, [REQUESTS_STATE.GETTERS.GET_STATE]),
    state() {
      return this[REQUESTS_STATE.GETTERS.GET_STATE]([API.notification.update.id, API.notification.grid.id])
    },
  },
  props: {
    service: {
      type: Object,
      default: () => ({}),
    },
  },
  methods: {
    ...mapActions(NOTIFICATIONS.NAMESPACE, [NOTIFICATIONS.ACTIONS.UPDATE_NOTIFICATIONS_REQUEST]),
    onSubmit(values) {
      this[NOTIFICATIONS.ACTIONS.UPDATE_NOTIFICATIONS_REQUEST]({
        id: this.service.id,
        settings: { settings: { ...values }, events: this.service.events },
      }).then(async (res) => {
        if (res) {
          this.isOpen = false
        }
      })
    },
  },
}
</script>
