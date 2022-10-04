<template>
  <base-modal v-model="isOpen" :title="$t('trashModal.reject.title')">
    <template #activator="{ attrs, on }">
      <base-button
        color="secondary"
        :attrs="attrs"
        :on="on"
        :button-title="$t('button.deny')"
        outlined
      />
    </template>
    <template #content>
      <div class="d-flex flex-column">
        <p>
          {{
            $t('trashModal.reject.body', [trashItem.id, trashItem.topologyName])
          }}
        </p>
      </div>
    </template>
    <template #actions>
      <base-button
        :button-title="$t('button.deny')"
        :on-click="rejectTrashItem"
      />
    </template>
  </base-modal>
</template>

<script>
import BaseModal from '@/components/commons/BaseModal'
import BaseButton from '@/components/commons/BaseButton'
import { callApi } from '@/utils/apiFetch'
import { API } from '@/api'
import showFlashMessage from '@/utils/flashMessage'
import { FLASH_MESSAGES_TYPES } from '@/store/flashMessages/types'
export default {
  name: 'TrashRejectModal',
  components: { BaseButton, BaseModal },
  props: {
    trashItem: {
      type: Object,
      required: true,
    },
  },
  data() {
    return {
      isOpen: false,
      id: null,
    }
  },
  methods: {
    async rejectTrashItem() {
      await callApi({
        requestData: API.trash.reject,
        params: {
          id: this.id,
        },
      })
      this.$emit('taskSubmitted')
      showFlashMessage(
        this.$t('flashMessage.rejected', {
          item: this.trashItem.name,
        }),
        FLASH_MESSAGES_TYPES.SUCCESS
      )
      this.isOpen = false
    },
  },
  watch: {
    trashItem: {
      immediate: true,
      deep: true,
      handler(trashItem) {
        this.id = trashItem.id
      },
    },
    isOpen(val) {
      if (!val) {
        this.id = null
      }
    },
  },
}
</script>

<style scoped></style>
