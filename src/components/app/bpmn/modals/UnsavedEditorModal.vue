<template>
  <modal-template v-model="isOpen" title="You have unsaved changes!">
    <v-col cols="12">
      <sending-button
        :sending-title="$t('button.sending.creating')"
        :is-sending="isSending"
        :flat="false"
        button-title="Save changes"
        :on-click="saveDiagram"
        :color="'primary'"
        class="mr-4"
      />
      <sending-button
        :sending-title="$t('button.sending.creating')"
        :is-sending="isSending"
        :flat="false"
        button-title="Discard changes"
        :on-click="exitEditor"
        :color="'error'"
      />
    </v-col>
  </modal-template>
</template>

<script>
import ModalTemplate from '@/components/commons/modal/ModalTemplate'
import SendingButton from '@/components/commons/button/AppButton'
import { TOPOLOGIES } from '@/store/modules/topologies/types'
import { ROUTES } from '@/services/enums/routerEnums'
import { mapActions } from 'vuex'
export default {
  name: 'UnsavedEditorModal',
  components: { SendingButton, ModalTemplate },
  props: {
    isSending: {
      type: Boolean,
      required: true,
    },
    getSavingResult: {
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
    ...mapActions(TOPOLOGIES.NAMESPACE, [TOPOLOGIES.ACTIONS.TOPOLOGY.GET_BY_ID]),
    async saveDiagram() {
      const isClone = await this.getSavingResult()
      this.isOpen = false
      if (isClone) {
        await this[TOPOLOGIES.ACTIONS.TOPOLOGY.GET_BY_ID](isClone)
        await this.$router.push({ name: ROUTES.EDITOR_PAGE })
      } else {
        this.$router.go(-1)
      }
    },
    exitEditor() {
      this.isOpen = false
      this.$router.go(-1)
    },
  },
}
</script>

<style scoped></style>
