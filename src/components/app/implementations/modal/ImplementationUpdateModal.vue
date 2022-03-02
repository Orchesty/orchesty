<template>
  <modal-template
    v-model="isOpen"
    :title="$t('implementation.updateModal.title')"
    :on-open="load"
    :on-confirm="() => $refs.form.submit()"
    :on-cancel="() => $refs.form.resetForm()"
    :on-close="() => $refs.form.resetForm()"
  >
    <template #default>
      <v-row dense>
        <v-col cols="12">
          <implementations-form ref="form" :implementation="implementations" :on-submit="submit" />
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
    <template #button>
      <v-btn icon color="primary" @click="isOpen = !isOpen">
        <v-icon> mdi-pencil </v-icon>
      </v-btn>
    </template>
  </modal-template>
</template>

<script>
import ImplementationsForm from '../form/ImplementationsForm'
import { IMPLEMENTATIONS } from '../../../../store/modules/implementations/types'
import { mapActions, mapGetters, mapState } from 'vuex'
import { REQUESTS_STATE } from '../../../../store/modules/api/types'
import { API } from '../../../../api'
import ModalTemplate from '@/components/commons/modal/ModalTemplate'
import AppButton from '@/components/commons/button/AppButton'
export default {
  name: 'ImplementationUpdateModal',
  data() {
    return {
      isOpen: false,
    }
  },
  components: { AppButton, ModalTemplate, ImplementationsForm },
  computed: {
    ...mapState(IMPLEMENTATIONS.NAMESPACE, ['implementations']),
    ...mapGetters(REQUESTS_STATE.NAMESPACE, [REQUESTS_STATE.GETTERS.GET_STATE]),
    state() {
      return this[REQUESTS_STATE.GETTERS.GET_STATE]([
        API.implementation.getById.id,
        API.implementation.update.id,
        API.implementation.getList.id,
      ])
    },
  },
  props: {
    itemId: {
      type: String,
      default: '',
    },
  },
  methods: {
    ...mapActions(IMPLEMENTATIONS.NAMESPACE, [
      IMPLEMENTATIONS.ACTIONS.UPDATE_IMPLEMENTATIONS_REQUEST,
      IMPLEMENTATIONS.ACTIONS.GET_IMPLEMENTATION_REQUEST,
    ]),
    async submit(values) {
      await this[IMPLEMENTATIONS.ACTIONS.UPDATE_IMPLEMENTATIONS_REQUEST]({ id: this.itemId, ...values }).then((res) => {
        if (res) {
          this.isOpen = false
        }
      })
    },
    async load() {
      await this[IMPLEMENTATIONS.ACTIONS.GET_IMPLEMENTATION_REQUEST]({ id: this.itemId })
    },
  },
}
</script>
