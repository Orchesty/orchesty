<template>
  <modal-template
    v-model="isOpen"
    :title="$t('folders.modals.create.title')"
    :on-cancel="() => $refs.form.reset()"
    :on-confirm="() => $refs.form.submit()"
  >
    <template #default>
      <v-col cols="12">
        <folder-form ref="form" :data="data" :on-submit="submit" />
      </v-col>
    </template>
    <template #sendingButton>
      <sending-button
        :sending-title="$t('button.sending.creating')"
        :is-sending="state.isSending"
        :flat="false"
        :button-title="$t('button.create')"
        :on-click="() => $refs.form.submit()"
        :color="'primary'"
      />
    </template>
  </modal-template>
</template>

<script>
import { events, EVENTS } from '../../../../events'
import ModalTemplate from '../../../commons/modal/ModalTemplate'
import { TOPOLOGIES } from '../../../../store/modules/topologies/types'
import { mapActions, mapGetters } from 'vuex'
import { REQUESTS_STATE } from '../../../../store/modules/api/types'
import { API } from '../../../../api'
import FolderForm from '../form/FolderForm'
import SendingButton from '@/components/commons/button/SendingButton'

export default {
  name: 'ModalCreateFolder',
  components: { SendingButton, FolderForm, ModalTemplate },
  data: () => ({
    isOpen: false,
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
          this.isOpen = false
          await this[TOPOLOGIES.ACTIONS.DATA.GET_TOPOLOGIES]()
        }
      })
    },
  },
  created() {
    events.listen(EVENTS.MODAL.FOLDER.CREATE, ({ topology }) => {
      this.isOpen = true
      if (!topology) topology = {}
      if (topology.id) topology = { parent: topology.id }
      this.data = topology
    })
  },
}
</script>
