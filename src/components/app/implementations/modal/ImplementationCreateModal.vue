<template>
  <div>
    <modal-template
      v-model="isOpen"
      :title="$t('modal.header.implementationCreate')"
      :on-confirm="() => $refs.form.submit()"
      :on-cancel="() => $refs.form.resetForm()"
      :on-close="() => $refs.form.resetForm()"
    >
      <template #default>
        <v-row dense>
          <v-col cols="12">
            <implementations-form ref="form" :on-submit="submit" />
          </v-col>
        </v-row>
      </template>
      <template #sendingButton>
        <v-row dense>
          <v-col cols="12" class="d-flex justify-end">
            <app-button
              :sending-title="$t('button.sending.creating')"
              :is-sending="state.isSending"
              :button-title="$t('button.create')"
              :on-click="() => $refs.form.submit()"
              :flat="false"
            />
          </v-col>
        </v-row>
      </template>
      <template #button>
        <app-button :on-click="() => (isOpen = !isOpen)" :button-title="$t('button.create')" />
      </template>
    </modal-template>
  </div>
</template>

<script>
import ImplementationsForm from '../form/ImplementationsForm'
import { IMPLEMENTATIONS } from '@/store/modules/implementations/types'
import { mapActions, mapGetters } from 'vuex'
import { REQUESTS_STATE } from '@/store/modules/api/types'
import { API } from '@/api'
import ModalTemplate from '@/components/commons/modal/ModalTemplate'
import AppButton from '@/components/commons/button/AppButton'

export default {
  name: 'ImplementationCreateModal',
  data() {
    return {
      isOpen: false,
    }
  },
  components: { AppButton, ModalTemplate, ImplementationsForm },
  computed: {
    ...mapGetters(REQUESTS_STATE.NAMESPACE, [REQUESTS_STATE.GETTERS.GET_STATE]),
    state() {
      return this[REQUESTS_STATE.GETTERS.GET_STATE]([API.implementation.create.id, API.implementation.getList.id])
    },
  },
  methods: {
    ...mapActions(IMPLEMENTATIONS.NAMESPACE, [IMPLEMENTATIONS.ACTIONS.CREATE_IMPLEMENTATIONS_REQUEST]),
    async submit(values) {
      await this[IMPLEMENTATIONS.ACTIONS.CREATE_IMPLEMENTATIONS_REQUEST](values).then((res) => {
        if (res) {
          this.isOpen = false
        }
      })
    },
  },
}
</script>
