<template>
  <modal-template v-model="isOpen" :on-confirm="() => submit()" :title="$t('folders.modals.delete.title')">
    <template #default>
      <v-col cols="12">
        {{ $t('folders.modals.delete.body', [data ? data.name : '']) }}
      </v-col>
    </template>
    <template #sendingButton>
      <app-button
        :sending-title="$t('button.sending.deleting')"
        :is-sending="state.isSending"
        :flat="false"
        :button-title="$t('button.delete')"
        :on-click="() => submit()"
        :color="'primary'"
      />
    </template>
  </modal-template>
</template>

<script>
import { events, EVENTS } from '../../../../services/utils/events'
import ModalTemplate from '../../../commons/modal/ModalTemplate'
import { mapActions, mapGetters } from 'vuex'
import { TOPOLOGIES } from '../../../../store/modules/topologies/types'
import { REQUESTS_STATE } from '../../../../store/modules/api/types'
import { API } from '../../../../api'
import AppButton from '@/components/commons/button/AppButton'

export default {
  name: 'ModalDeleteFolder',
  components: { AppButton, ModalTemplate },
  data: () => ({
    isOpen: false,
    data: null,
  }),
  computed: {
    ...mapGetters(REQUESTS_STATE.NAMESPACE, [REQUESTS_STATE.GETTERS.GET_STATE]),
    state() {
      return this[REQUESTS_STATE.GETTERS.GET_STATE](API.folder.delete.id)
    },
  },
  methods: {
    ...mapActions(TOPOLOGIES.NAMESPACE, [TOPOLOGIES.ACTIONS.FOLDER.DELETE, TOPOLOGIES.ACTIONS.DATA.GET_TOPOLOGIES]),
    async submit() {
      await this[TOPOLOGIES.ACTIONS.FOLDER.DELETE]({
        id: this.data.id,
      }).then((res) => {
        if (res) {
          this.isOpen = false
        }
      })
    },
  },
  created() {
    events.listen(EVENTS.MODAL.FOLDER.DELETE, ({ topology }) => {
      this.data = topology
      this.isOpen = true
    })
  },
}
</script>

<style></style>
