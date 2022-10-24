<template>
  <v-dialog
    v-model="isOpen"
    :width="width"
    content-class="modal-template"
    @keydown.enter="enterHandler"
  >
    <template #activator="{ on }">
      <slot name="button" v-on="on" />
    </template>
    <v-card flat class="h-100">
      <v-toolbar color="primary" dark>
        <v-btn icon dark @click="cancel">
          <v-icon>mdi-close</v-icon>
        </v-btn>
        <v-toolbar-title v-if="title" class="pl-0">
          <h1 class="title font-weight-bold">{{ title }}</h1>
        </v-toolbar-title>
        <div class="flex-grow-1"></div>
        <v-toolbar-items>
          <slot name="toolbar-buttons"></slot>
          <app-button
            v-if="confirmBtnText"
            :button-title="confirmBtnText"
            :sending-title="sendingTitle"
            :on-click="confirm"
            :is-sending="isSending"
            color="white"
          />
        </v-toolbar-items>
      </v-toolbar>
      <v-container>
        <slot></slot>
        <slot name="sendingButton"> </slot>
      </v-container>
    </v-card>
  </v-dialog>
</template>

<script>
import AppButton from "@/components/commons/button/AppButton"

export default {
  name: "ModalTemplate",
  components: { AppButton },
  data: () => ({
    isOpen: false,
  }),
  props: {
    async: {
      type: Boolean,
      default: false,
    },
    disableEnterConfirm: {
      type: Boolean,
      default: false,
    },
    value: {
      type: Boolean,
      required: true,
    },
    sendingTitle: {
      type: String,
      default: "",
    },
    title: {
      type: String,
      required: false,
      default: "",
    },
    body: {
      type: String,
      required: false,
      default: () => "",
    },
    cancelBtnText: {
      type: String,
      required: false,
      default: "",
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
      default: "",
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
    width: {
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
    enterHandler() {
      if (!this.disableEnterConfirm) {
        this.onConfirm()
      }
    },
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
      this.$emit("input", newVal)
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
.h-100 {
  height: 100%;
}
</style>
