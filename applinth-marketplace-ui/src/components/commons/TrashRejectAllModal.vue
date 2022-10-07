<template>
  <base-modal v-model="isOpen" :title="$t('trashModal.rejectAll.title')">
    <template #activator="{ attrs, on }">
      <base-button
        :attrs="attrs"
        :on="on"
        :button-title="$t('button.deny')"
        :min-width="50"
        :height="24"
        outlined
      />
    </template>
    <template #actions>
      <base-button
        :button-title="$t('button.cancel')"
        :on-click="onCancel"
        outlined
      />
      <base-button :button-title="$t('button.deny')" :on-click="onConfirm" />
    </template>
  </base-modal>
</template>

<script>
import BaseModal from '@/components/commons/BaseModal'
import BaseButton from '@/components/commons/BaseButton'

export default {
  name: 'TrashRejectAllModal',
  components: { BaseButton, BaseModal },
  data() {
    return {
      isOpen: false,
    }
  },
  methods: {
    onConfirm() {
      this.$emit('confirm')
      this.isOpen = false
    },
    onCancel() {
      this.isOpen = false
    },
  },
}
</script>
