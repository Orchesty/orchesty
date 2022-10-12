<template>
  <modal-template
    v-model="isOpen"
    :width="type === 'update' ? 800 : 500"
    :title="$t(`userTask.modal.${type}.title`)"
    :on-confirm="confirm"
    :disable-enter-confirm="isActionUpdate"
  >
    <template #default>
      <v-row dense>
        <template v-if="!isActionUpdate">
          <v-col cols="12">
            {{ $t(`userTask.modal.${type}.body`, [bodyMessage]) }}
          </v-col>
        </template>
        <template v-else>
          <v-col cols="12" class="mb-3">
            <span class="pb-1">Headers:</span>
            <json-editor v-model="headers" />
          </v-col>
          <v-col cols="12">
            <span class="pb-1">Body:</span>
            <json-editor v-if="isBodyJson" v-model="body" />
          </v-col>
        </template>
      </v-row>
    </template>
    <template #sendingButton>
      <v-row dense>
        <v-col cols="12" class="d-flex justify-end">
          <app-button
            :sending-title="$t('button.sending.creating')"
            :is-sending="state.isSending"
            :button-title="$t(`topologies.userTask.buttons.${type}`)"
            :on-click="confirm"
          />
        </v-col>
      </v-row>
    </template>
    <template #button>
      <app-button
        :sending-title="$t('button.sending.creating')"
        :is-sending="state.isSending"
        :button-title="$t(`topologies.userTask.buttons.${type}`)"
        :on-click="() => (isOpen = !isOpen)"
        :color="color"
        :custom-class="buttonClass"
        :flat="text"
        :disabled="disabled"
      />
    </template>
  </modal-template>
</template>

<script>
import { mapGetters } from 'vuex'
import { REQUESTS_STATE } from '@/store/modules/api/types'
import { API } from '@/api'
import ModalTemplate from '@/components/commons/modal/ModalTemplate'
import AppButton from '@/components/commons/button/AppButton'
import JsonEditor from '@/components/app/userTasks/modal/JsonEditor'

export default {
  name: 'UserTaskActionsModal',
  components: { JsonEditor, AppButton, ModalTemplate },
  data() {
    return {
      isOpen: false,
      headers: null,
      body: null,
      isBodyJson: false,
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
      default: false,
    },
    message: {
      type: Object,
      default: () => {},
    },
    onSubmit: {
      type: Function,
      required: true,
    },
  },
  computed: {
    ...mapGetters(REQUESTS_STATE.NAMESPACE, [REQUESTS_STATE.GETTERS.GET_STATE]),
    state() {
      return this[REQUESTS_STATE.GETTERS.GET_STATE](API.userTask[this.type].id)
    },
    isActionUpdate() {
      return this.type === 'update'
    },
    buttonClass() {
      return `${this.text ? '' : 'white--text '} ${this.ml ? 'ml-2' : 'mr-2'}`
    },
    bodyMessage() {
      return this.localizeItemCount(this.$i18n.locale, this.selected.length)
    },
  },
  watch: {
    message: {
      immediate: true,
      deep: true,
      handler(message) {
        if (message) {
          this.headers = message.headers
          this.checkBodyDataFormat(message.body)
        }
      },
    },
  },
  methods: {
    confirm() {
      if (this.type === 'update') {
        this.onSubmit({ headers: this.headers, body: JSON.stringify(this.body) }).then((res) => {
          if (res) {
            this.$emit('reset')
            this.isOpen = false
          }
        })
      } else {
        this.onSubmit().then((res) => {
          if (res) {
            this.$emit('reset')
            this.isOpen = false
          }
        })
      }
    },
    localizeItemCount(locale, count = 1) {
      const format = new Intl.PluralRules(locale, {
        type: 'cardinal',
      })

      const messages = {
        en: {
          one: 'item',
          other: 'items',
        },
      }

      return `${count} ${messages[locale][format.select(count)]}`
    },
    checkBodyDataFormat(body) {
      try {
        this.body = JSON.parse(body)
        this.isBodyJson = true
      } catch (e) {
        this.isBodyJson = false
      }
    },
    onError() {
      console.log('error')
    },
  },
}
</script>

<style scoped></style>
