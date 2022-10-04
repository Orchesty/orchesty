<template>
  <base-modal v-model="isOpen" :title="$t('trashModal.accept.title')">
    <template #activator="{ attrs, on }">
      <base-button
        color="primary"
        :attrs="attrs"
        :on="on"
        :button-title="$t('button.accept')"
      />
    </template>
    <template #content>
      <div class="d-flex flex-column">
        <p>
          {{
            $t('trashModal.accept.body', [trashItem.id, trashItem.topologyName])
          }}
        </p>
      </div>
    </template>
    <template #actions>
      <base-button
        :button-title="$t('button.accept')"
        :on-click="acceptTrashItem"
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
  name: 'TrashAcceptModal',
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
    async acceptTrashItem() {
      await callApi({
        requestData: API.trash.accept,
        params: {
          id: this.id,
        },
      })
      this.$emit('taskSubmitted')
      showFlashMessage(
        this.$t('flashMessage.accepted', {
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
