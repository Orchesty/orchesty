<template>
  <v-dialog
    v-model="isOpen"
    width="600"
    content-class="modal-template"
    :max-width="maxWidth"
    @keydown.enter="onConfirm"
  >
    <template #activator="{ on }">
      <slot name="button" v-on="on" />
    </template>
    <v-card flat>
      <v-toolbar color="primary" dark>
        <v-btn icon dark @click="cancel">
          <v-icon>mdi-close</v-icon>
        </v-btn>
        <v-toolbar-title v-if="title">
          {{ title }}
        </v-toolbar-title>
        <div class="flex-grow-1"></div>
        <v-toolbar-items>
          <slot name="toolbar-buttons"></slot>
          <sending-button
            v-if="confirmBtnText"
            :button-title="confirmBtnText"
            :sending-title="sendingTitle"
            :on-click="confirm"
            :is-sending="isSending"
            color="white"
          />
        </v-toolbar-items>
      </v-toolbar>
      <slot></slot>
      <v-col cols="12" class="text-end">
        <slot name="sendingButton"> </slot>
      </v-col>
    </v-card>
  </v-dialog>
</template>

<script>
import SendingButton from '../button/SendingButton'

export default {
  name: 'ModalTemplate',
  components: { SendingButton },
  data: () => ({
    isOpen: false,
  }),
  props: {
    async: {
      type: Boolean,
      default: false,
    },
    value: {
      type: Boolean,
      required: true,
    },
    sendingTitle: {
      type: String,
      default: '',
    },
    title: {
      type: String,
      required: false,
      default: '',
    },
    body: {
      type: String,
      required: false,
      default: () => '',
    },
    cancelBtnText: {
      type: String,
      required: false,
      default: '',
    },
    isSending: {
      type: Boolean,
      required: false,
      default: false,
    },
    onCancel: {
      type: Function,
      required: false,
      default: null,
    },
    confirmBtnText: {
      type: String,
      required: false,
      default: '',
    },
    onConfirm: {
      type: Function,
      required: false,
      default: null,
    },
    onClose: {
      type: Function,
      required: false,
      default: null,
    },
    closeable: {
      type: Boolean,
      default: () => true,
    },
    maxWidth: {
      type: Number,
      default: () => 500,
    },
    onOpen: {
      type: Function,
      required: false,
      default: null,
    },
  },
  methods: {
    cancel() {
      this.isOpen = false
      if (this.onCancel) {
        this.onCancel()
      }
    },
  },
  watch: {
    value(newVal) {
      if (newVal === true && this.onOpen) {
        this.onOpen()
      }
      if (newVal === false && this.onClose) {
        this.onClose()
      }
      this.isOpen = newVal
    },
    isOpen(newVal) {
      this.$emit('input', newVal)
    },
  },
  created() {
    if (this.value) {
      this.isOpen = this.value
    }
  },
}
</script>

<style lang="scss">
.modal-template {
  display: flex;
}
</style>
