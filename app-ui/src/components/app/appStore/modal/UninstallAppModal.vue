<template>
  <modal-template v-model="isOpen" :title="$t('modal.header.appUninstall')">
    <template #button>
      <app-button
        color="error"
        :button-title="$t('button.uninstall')"
        :loading="isUninstalling"
        :on-click="() => (isOpen = !isOpen)"
      />
    </template>
    <template #default>
      <v-row dense>
        <v-col cols="12">
          {{ $t('modal.text.appUninstall', { app: appName }) }}
        </v-col>
      </v-row>
    </template>
    <template #sendingButton>
      <v-row dense>
        <v-col cols="12" class="d-flex justify-end">
          <app-button :button-title="$t('button.cancel')" :on-click="onReject" />
          <app-button
            class="ml-2"
            color="error"
            :button-title="$t('button.uninstall')"
            :on-click="onConfirm"
            :loading="isUninstalling"
          />
        </v-col>
      </v-row>
    </template>
  </modal-template>
</template>

<script>
import ModalTemplate from '@/components/commons/modal/ModalTemplate'
import AppButton from '@/components/commons/button/AppButton'
export default {
  name: 'UninstallAppModal',
  components: { AppButton, ModalTemplate },
  props: {
    appName: {
      type: String,
      required: true,
    },
    onClick: {
      type: Function,
      required: true,
    },
    isUninstalling: {
      type: Boolean,
      required: true,
    },
  },
  data() {
    return {
      isOpen: false,
    }
  },
  methods: {
    async onConfirm() {
      await this.onClick()
      this.isOpen = false
    },
    onReject() {
      this.isOpen = false
    },
  },
}
</script>

<style scoped></style>
