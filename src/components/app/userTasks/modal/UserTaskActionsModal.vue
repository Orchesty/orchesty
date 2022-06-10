<template>
  <modal-template
    v-model="isOpen"
    :width="type === 'update' ? 800 : 500"
    :title="$t(`userTask.modal.${type}.title`)"
    :on-confirm="confirm"
  >
    <template #default>
      <v-row dense>
        <v-col cols="12">
          {{ type !== 'update' ? $t(`userTask.modal.${type}.body`, [bodyMessage]) : null }}
        </v-col>
        <v-col v-if="type === 'update'" cols="12">
          <span class="pb-1">Headers:</span>
          <v-jsoneditor v-model="headerObject" :options="options" :plus="false" height="300px" @error="onError" />
        </v-col>
        <v-col v-if="type === 'update'" cols="12">
          <span class="pb-1">Body:</span>
          <v-jsoneditor v-model="bodyObject" :options="options" :plus="false" height="300px" @error="onError" />
        </v-col>
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
import VJsoneditor from 'v-jsoneditor'

export default {
  name: 'UserTaskActionsModal',
  components: { AppButton, ModalTemplate, VJsoneditor },
  data() {
    return {
      isOpen: false,
      headers: [],
      body: '',
      options: {
        mode: 'tree',
        mainMenuBar: false,
      },
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
    headerObject: {
      get() {
        return JSON.parse(this.headers)
      },
      set(headers) {
        this.headers = JSON.stringify(headers)
      },
    },
    bodyObject: {
      get() {
        return JSON.parse(this.body)
      },
      set(body) {
        this.body = JSON.stringify(body)
      },
    },
  },
  watch: {
    data: {
      immediate: true,
      handler(data) {
        if (data && data.headers) {
          this.headers = JSON.stringify(data.headers)
          if (data.body) {
            this.body = JSON.stringify(data.body)
          }
        }
      },
    },
    deep: true,
  },
  methods: {
    async confirm() {
      if (this.type === 'update') {
        await this.method({ headers: JSON.parse(this.headers), body: JSON.parse(this.body) }).then((res) => {
          if (res) {
            this.$emit('reset')
            this.isOpen = false
          }
        })
      } else {
        await this.method().then((res) => {
          if (res) {
            this.$emit('reset')
            this.isOpen = false
          }
        })
      }
    },
    onError() {
      console.log('error')
    },
  },
}
</script>

<style scoped></style>
