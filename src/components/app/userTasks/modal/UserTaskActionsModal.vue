<template>
  <modal-template
    v-model="isOpen"
    :max-width="type === 'update' ? 800 : 500"
    :title="$t(`userTask.modal.${type}.title`)"
    :on-confirm="confirm"
    :on-close="type !== 'update' ? () => $emit('reset') : undefined"
  >
    <template #default>
      <v-col cols="12">
        {{ type !== 'update' ? $t(`userTask.modal.${type}.body`, [bodyMessage]) : null }}
      </v-col>
      <v-col v-if="type === 'update'" cols="12">
        <v-textarea v-model="headers" no-resize :label="$t('userTask.modal.update.headers')" />
      </v-col>
      <v-col v-if="type === 'update'" cols="12">
        <v-textarea v-model="body" no-resize :label="$t('userTask.modal.update.body')" />
      </v-col>
    </template>
    <template #sendingButton>
      <sending-button
        :sending-title="$t('button.sending.creating')"
        :is-sending="state.isSending"
        :button-title="$t(`topologies.userTask.buttons.${type}`)"
        :on-click="confirm"
        :flat="false"
      />
    </template>
    <template #button>
      <v-btn
        :text="text"
        :disabled="disabled"
        :color="color"
        class="fixed-width"
        :class="buttonClass"
        min-width="110"
        @click="isOpen = !isOpen"
      >
        <span>{{ $t(`topologies.userTask.buttons.${type}`) }}</span>
      </v-btn>
    </template>
  </modal-template>
</template>

<script>
import { mapGetters } from 'vuex'
import { REQUESTS_STATE } from '@/store/modules/api/types'
import { API } from '@/api'
import ModalTemplate from '@/components/commons/modal/ModalTemplate'
import SendingButton from '@/components/commons/button/SendingButton'
export default {
  name: 'UserTaskActionsModal',
  components: { SendingButton, ModalTemplate },
  data() {
    return {
      isOpen: false,
      headers: [],
      body: '',
    }
  },
  props: {
    ml: {
      type: Boolean,
      default: false,
    },
    text: {
      type: Boolean,
      default: false,
    },
    selected: {
      type: Array,
      required: true,
    },
    type: {
      type: String,
      required: true,
    },
    color: {
      type: String,
      required: true,
    },
    disabled: {
      type: Boolean,
      required: false,
      default: false,
    },
    data: {
      type: Object,
      default: () => {},
    },
    method: {
      type: Function,
      required: true,
    },
  },
  computed: {
    ...mapGetters(REQUESTS_STATE.NAMESPACE, [REQUESTS_STATE.GETTERS.GET_STATE]),
    state() {
      return this[REQUESTS_STATE.GETTERS.GET_STATE](API.userTask[this.type].id)
    },
    buttonClass() {
      return `${this.text ? '' : 'white--text '} ${this.ml ? 'ml-2' : 'mr-2'}`
    },
    bodyMessage() {
      return this.selected.length
    },
  },
  watch: {
    data: {
      immediate: true,
      handler(data) {
        if (data && data.headers) {
          this.headers = JSON.stringify(data.headers, null, 2)
          if (data.body) {
            this.body = data.body
          }
        }
      },
    },
    deep: true,
  },
  methods: {
    async confirm() {
      if (this.type === 'update') {
        await this.method({ headers: JSON.parse(this.headers), body: this.body }).then((res) => {
          if (res) {
            this.isOpen = false
          }
        })
      } else {
        await this.method().then((res) => {
          if (res) {
            this.isOpen = false
          }
        })
      }
    },
  },
}
</script>

<style scoped></style>
