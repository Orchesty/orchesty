<template>
  <modal-template v-model="isOpen" :title="$t('modal.header.unsavedChanges')">
    <v-col cols="12" class="text-right">
      <app-button
        :sending-title="$t('button.sending.creating')"
        :is-sending="isSending"
        :button-title="$t('button.discard')"
        :on-click="exitEditor"
        color="secondary"
        outlined
        class="mr-4"
      />
      <app-button
        :sending-title="$t('button.sending.saving')"
        :is-sending="isSending"
        :button-title="$t('button.save')"
        :on-click="saveDiagram"
        color="primary"
      />
    </v-col>
  </modal-template>
</template>

<script>
import ModalTemplate from "@/components/commons/modal/ModalTemplate"
import { TOPOLOGIES } from "@/store/modules/topologies/types"
import { mapActions } from "vuex"
import AppButton from "@/components/commons/button/AppButton"
export default {
  name: "UnsavedEditorModal",
  components: { AppButton, ModalTemplate },
  props: {
    isSending: {
      type: Boolean,
      required: true,
    },
    saveHasNewId: {
      type: Function,
      required: true,
    },
    redirectFunction: {
      type: Function,
      required: true,
    },
  },
  data() {
    return {
      isOpen: false,
    }
  },
  methods: {
    ...mapActions(TOPOLOGIES.NAMESPACE, [
      TOPOLOGIES.ACTIONS.TOPOLOGY.GET_BY_ID,
    ]),
    async saveDiagram() {
      const newId = await this.saveHasNewId()
      this.isOpen = false
      if (newId) {
        this.redirectFunction()
        await this[TOPOLOGIES.ACTIONS.TOPOLOGY.GET_BY_ID](newId)
      } else {
        this.redirectFunction()
      }
    },
    exitEditor() {
      this.isOpen = false
      this.redirectFunction()
    },
  },
}
</script>

<style scoped></style>
