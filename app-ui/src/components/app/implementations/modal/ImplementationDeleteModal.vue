<template>
  <modal-template
    v-model="isOpen"
    :title="$t('modal.header.implementationDelete')"
    :on-confirm="submit"
  >
    <template #default>
      <v-row dense>
        <v-col cols="12">
          {{ $t("modal.text.implementationDelete") }}
        </v-col>
      </v-row>
    </template>
    <template #sendingButton>
      <v-row dense>
        <v-col cols="12" class="d-flex justify-end">
          <app-button
            :sending-title="$t('button.sending.deleting')"
            :is-sending="state.isSending"
            :button-title="$t('button.delete')"
            :on-click="submit"
            :flat="false"
          />
        </v-col>
      </v-row>
    </template>
    <template #button>
      <v-btn class="ma-0" color="primary" icon @click="isOpen = !isOpen">
        <v-icon> delete </v-icon>
      </v-btn>
    </template>
  </modal-template>
</template>

<script>
import { mapActions, mapGetters } from "vuex"
import { REQUESTS_STATE } from "@/store/modules/api/types"
import { API } from "@/api"
import { IMPLEMENTATIONS } from "@/store/modules/implementations/types"
import ModalTemplate from "@/components/commons/modal/ModalTemplate"
import AppButton from "@/components/commons/button/AppButton"

export default {
  name: "ImplementationDeleteModal",
  components: { AppButton, ModalTemplate },
  data() {
    return {
      isOpen: false,
    }
  },
  computed: {
    ...mapGetters(REQUESTS_STATE.NAMESPACE, [REQUESTS_STATE.GETTERS.GET_STATE]),
    state() {
      return this[REQUESTS_STATE.GETTERS.GET_STATE]([
        API.implementation.delete.id,
        API.implementation.getList.id,
      ])
    },
  },
  props: {
    itemId: {
      type: String,
      default: "",
    },
  },
  methods: {
    ...mapActions(IMPLEMENTATIONS.NAMESPACE, [
      IMPLEMENTATIONS.ACTIONS.DELETE_IMPLEMENTATIONS_REQUEST,
    ]),
    async submit() {
      await this[IMPLEMENTATIONS.ACTIONS.DELETE_IMPLEMENTATIONS_REQUEST]({
        id: this.itemId,
      }).then((res) => {
        if (res) {
          this.isOpen = false
        }
      })
    },
  },
}
</script>
