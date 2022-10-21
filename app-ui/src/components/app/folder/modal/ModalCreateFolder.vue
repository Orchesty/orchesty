<template>
  <modal-template
    v-model="isOpen"
    :title="$t('modal.header.createFolder')"
    :on-close="onClose"
    :on-confirm="() => $refs.form.submit()"
  >
    <template #default>
      <v-row dense>
        <v-col cols="12">
          <folder-form ref="form" :callback-data="callbackData" :on-submit="submit" />
        </v-col>
      </v-row>
    </template>
    <template #sendingButton>
      <v-row dense>
        <v-col cols="12" class="d-flex justify-end">
          <app-button
            :sending-title="$t('button.sending.creating')"
            :is-sending="state.isSending"
            :flat="false"
            :button-title="$t('button.create')"
            :on-click="() => $refs.form.submit()"
            :color="'primary'"
          />
        </v-col>
      </v-row>
    </template>
  </modal-template>
</template>

<script>
import { events, EVENTS } from '../../../../services/utils/events'
import ModalTemplate from '../../../commons/modal/ModalTemplate'
import { TOPOLOGIES } from '../../../../store/modules/topologies/types'
import { mapActions, mapGetters } from 'vuex'
import { REQUESTS_STATE } from '../../../../store/modules/api/types'
import { API } from '../../../../api'
import FolderForm from '../form/FolderForm'
import AppButton from '@/components/commons/button/AppButton'

export default {
  name: 'ModalCreateFolder',
  components: { AppButton, FolderForm, ModalTemplate },
  data: () => ({
    isOpen: false,
    callbackData: null,
    data: null,
  }),
  computed: {
    ...mapGetters(REQUESTS_STATE.NAMESPACE, [REQUESTS_STATE.GETTERS.GET_STATE]),
    state() {
      return this[REQUESTS_STATE.GETTERS.GET_STATE]([API.folder.create.id])
    },
  },
  methods: {
    ...mapActions(TOPOLOGIES.NAMESPACE, [TOPOLOGIES.ACTIONS.FOLDER.CREATE, TOPOLOGIES.ACTIONS.DATA.GET_TOPOLOGIES]),
    async submit(form) {
      await this[TOPOLOGIES.ACTIONS.FOLDER.CREATE](form).then(async (res) => {
        if (res) {
          await this[TOPOLOGIES.ACTIONS.DATA.GET_TOPOLOGIES]()
          this.isOpen = false
        }
      })
    },
    onClose() {
      this.callbackData = null
      this.folderId = null
    },
  },
  created() {
    events.listen(EVENTS.MODAL.FOLDER.CREATE, ({ topology }) => {
      this.isOpen = true
      if (!topology) topology = {}
      if (topology.id) topology = { parent: topology.id }
      this.callbackData = topology
    })
  },
}
</script>
