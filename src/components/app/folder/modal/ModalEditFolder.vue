<template>
  <modal-template
    v-model="isOpen"
    :title="$t('folders.modals.edit.title')"
    :on-cancel="() => $refs.form.reset()"
    :on-close="() => $refs.form.reset()"
    :on-confirm="() => $refs.form.submit()"
  >
    <template #default>
      <v-col cols="12">
        <folder-form ref="form" :data="data" :sending-btn="false" :on-submit="submit" />
      </v-col>
    </template>
    <template #sendingButton>
      <sending-button
        :sending-title="$t('button.sending.editing')"
        :is-sending="state.isSending"
        :flat="false"
        :button-title="$t('button.edit')"
        :on-click="() => $refs.form.submit()"
        :color="'primary'"
      />
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
import SendingButton from '@/components/commons/button/AppButton'

export default {
  name: 'ModalEditFolder',
  components: { SendingButton, FolderForm, ModalTemplate },
  data: () => ({
    isOpen: false,
    data: null,
    folderId: null,
  }),
  computed: {
    ...mapGetters(REQUESTS_STATE.NAMESPACE, [REQUESTS_STATE.GETTERS.GET_STATE]),
    state() {
      return this[REQUESTS_STATE.GETTERS.GET_STATE]([API.folder.edit.id])
    },
  },
  methods: {
    ...mapActions(TOPOLOGIES.NAMESPACE, [TOPOLOGIES.ACTIONS.FOLDER.EDIT, TOPOLOGIES.ACTIONS.DATA.GET_TOPOLOGIES]),
    submit(form) {
      this[TOPOLOGIES.ACTIONS.FOLDER.EDIT]({ ...form, id: this.folderId }).then(async (res) => {
        if (res) {
          this.isOpen = false
          await this[TOPOLOGIES.ACTIONS.DATA.GET_TOPOLOGIES]()
        }
      })
    },
  },
  created() {
    events.listen(EVENTS.MODAL.FOLDER.EDIT, ({ topology }) => {
      this.isOpen = true
      if (!topology) topology = {}
      this.data = topology
      this.folderId = topology.id
    })
  },
}
</script>
