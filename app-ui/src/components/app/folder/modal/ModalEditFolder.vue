<template>
  <modal-template
    v-model="isOpen"
    :title="$t('modal.header.editFolder')"
    :on-close="onClose"
    :on-confirm="() => $refs.form.submit()"
  >
    <template #default>
      <v-row dense>
        <v-col cols="12">
          <folder-form
            ref="form"
            :callback-data="callbackData"
            :on-submit="submit"
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
            :flat="false"
            :button-title="$t('button.edit')"
            :on-click="() => $refs.form.submit()"
            :color="'primary'"
          />
        </v-col>
      </v-row>
    </template>
  </modal-template>
</template>

<script>
import { events, EVENTS } from "../../../../services/utils/events"
import ModalTemplate from "../../../commons/modal/ModalTemplate"
import { TOPOLOGIES } from "../../../../store/modules/topologies/types"
import { mapActions, mapGetters } from "vuex"
import { REQUESTS_STATE } from "../../../../store/modules/api/types"
import { API } from "../../../../api"
import FolderForm from "../form/FolderForm"
import AppButton from "@/components/commons/button/AppButton"

export default {
  name: "ModalEditFolder",
  components: { AppButton, FolderForm, ModalTemplate },
  data: () => ({
    isOpen: false,
    callbackData: null,
    folderId: null,
  }),
  computed: {
    ...mapGetters(REQUESTS_STATE.NAMESPACE, [REQUESTS_STATE.GETTERS.GET_STATE]),
    state() {
      return this[REQUESTS_STATE.GETTERS.GET_STATE]([API.folder.edit.id])
    },
  },
  methods: {
    ...mapActions(TOPOLOGIES.NAMESPACE, [
      TOPOLOGIES.ACTIONS.FOLDER.EDIT,
      TOPOLOGIES.ACTIONS.DATA.GET_TOPOLOGIES,
    ]),
    submit(form) {
      this[TOPOLOGIES.ACTIONS.FOLDER.EDIT]({ ...form, id: this.folderId }).then(
        async (res) => {
          if (res) {
            await this[TOPOLOGIES.ACTIONS.DATA.GET_TOPOLOGIES]()
            this.isOpen = false
          }
        }
      )
    },
    onClose() {
      this.callbackData = null
      this.folderId = null
    },
  },
  created() {
    events.listen(EVENTS.MODAL.FOLDER.EDIT, ({ topology }) => {
      this.isOpen = true
      if (!topology) topology = {}
      this.callbackData = topology
      this.folderId = topology.id
    })
  },
}
</script>
