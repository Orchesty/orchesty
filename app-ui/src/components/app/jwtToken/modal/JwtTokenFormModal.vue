<template>
  <ModalTemplate
    v-model="isOpen"
    :title="$t('modal.header.createToken')"
    :on-confirm="() => $refs.form.submit()"
    :on-close="clearForm"
    :value="isOpen"
  >
    <template #default>
      <v-row dense>
        <v-col cols="12">
          <JwtTokenForm ref="form" @submit="handleSubmit" />
        </v-col>
      </v-row>
    </template>
    <template #sendingButton>
      <v-row dense>
        <v-col cols="12" class="d-flex justify-end">
          <AppButton
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
    <template #button>
      <AppButton
        :sending-title="$t(`button.sending.accept`)"
        :is-sending="state.isSending"
        :button-title="$t(`button.create`)"
        :on-click="() => (isOpen = !isOpen)"
        color="primary"
      />
    </template>
  </ModalTemplate>
</template>

<script>
import { mapActions, mapGetters } from "vuex"
import { JWT_TOKENS } from "@/store/modules/jwtTokens/types"
import { REQUESTS_STATE } from "@/store/modules/api/types"
import { API } from "@/api"
import JwtTokenForm from "@/components/app/jwtToken/form/JwtTokenForm"
import AppButton from "@/components/commons/button/AppButton"
import ModalTemplate from "@/components/commons/modal/ModalTemplate"
import { events, EVENTS } from "@/services/utils/events"

export default {
  name: "JwtTokenFormModal",
  components: { JwtTokenForm, ModalTemplate, AppButton },
  data: () => ({
    isOpen: false,
    events,
  }),
  computed: {
    ...mapGetters(REQUESTS_STATE.NAMESPACE, [REQUESTS_STATE.GETTERS.GET_STATE]),
    state() {
      return this[REQUESTS_STATE.GETTERS.GET_STATE]([API.folder.edit.id])
    },
  },
  methods: {
    ...mapActions(JWT_TOKENS.NAMESPACE, [JWT_TOKENS.ACTIONS.CREATE]),
    async handleSubmit(data) {
      const result = await this[JWT_TOKENS.ACTIONS.CREATE](data)

      if (result) {
        this.events.emit(EVENTS.MODAL.JWT_TOKEN.CREATE)
        this.isOpen = false
        this.clearForm()
      }
    },
    clearForm() {
      this.$refs.form.clear()
    },
  },
}
</script>
