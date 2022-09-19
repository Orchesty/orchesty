<template>
  <base-modal v-model="isOpen" :title="$t('application.appUninstall')">
    <template #activator="{ attrs, on }">
      <base-button
        color="error"
        :button-title="$t('button.uninstall')"
        :loading="isUninstalling"
        :attrs="attrs"
        :on="on"
      />
    </template>
    <template #content>
      <div class="d-flex flex-column">
        <p class="text-body-1">
          {{ $t('application.appUninstallConfirmation', { app: appName }) }}
        </p>
      </div>
    </template>
    <template #actions>
      <base-button :button-title="$t('button.cancel')" :on-click="onReject" />
      <base-button
        color="error"
        :button-title="$t('button.uninstall')"
        :on-click="onConfirm"
        :loading="isUninstalling"
      />
    </template>
  </base-modal>
</template>

<script>
import BaseModal from '@/components/commons/BaseModal'
import BaseButton from '@/components/commons/BaseButton'

export default {
  name: 'UninstallAppModal',
  components: { BaseButton, BaseModal },
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
