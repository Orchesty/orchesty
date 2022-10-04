<template>
  <base-modal v-model="isOpen" :title="$t('trashModal.update.title')">
    <template #activator="{ attrs, on }">
      <base-button
        color="primary"
        :attrs="attrs"
        :on="on"
        :button-title="$t('button.update')"
        outlined
      />
    </template>
    <template #content>
      <div class="d-flex flex-column">
        <json-editor v-if="isBodyJson" v-model="body" />
      </div>
    </template>
    <template #actions>
      <base-button
        :button-title="$t('button.update')"
        :on-click="updateTrashItem"
      />
    </template>
  </base-modal>
</template>

<script>
import BaseModal from '@/components/commons/BaseModal'
import BaseButton from '@/components/commons/BaseButton'
import JsonEditor from '@/components/commons/JsonEditor'
import { callApi } from '@/utils/apiFetch'
import { API } from '@/api'
import { FLASH_MESSAGES_TYPES } from '@/store/flashMessages/types'
import showFlashMessage from '@/utils/flashMessage'
export default {
  name: 'TrashUpdateModal',
  components: { JsonEditor, BaseButton, BaseModal },
  props: {
    trashItem: {
      type: Object,
      required: true,
    },
  },
  data() {
    return {
      isOpen: false,
      body: null,
      headers: null,
      id: null,
      isBodyJson: false,
    }
  },
  methods: {
    async updateTrashItem() {
      await callApi({
        requestData: API.trash.update,
        params: {
          id: this.id,
          headers: this.headers,
          body: JSON.stringify(this.body),
        },
      })
      this.$emit('refreshItemData')
      showFlashMessage(
        this.$t('flashMessage.updated', { item: this.trashItem.name }),
        FLASH_MESSAGES_TYPES.SUCCESS
      )
      this.isOpen = false
    },
    checkBodyDataFormat(body) {
      try {
        this.body = JSON.parse(body)
        this.isBodyJson = true
      } catch {
        this.isBodyJson = false
      }
    },
  },
  watch: {
    trashItem: {
      immediate: true,
      deep: true,
      handler(trashItem) {
        this.id = trashItem.id
        this.headers = trashItem.message.headers

        this.checkBodyDataFormat(trashItem.message.body)
      },
    },
    isOpen(val) {
      if (!val) {
        this.headers = this.trashItem.message.headers
        this.checkBodyDataFormat(this.trashItem.message.body)
      }
    },
  },
}
</script>

<style scoped></style>
