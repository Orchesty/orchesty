<template>
  <base-modal v-model="isOpen" :title="$t('trashModal.reject.title')">
    <template #activator="{ attrs, on }">
      <base-button
        color="secondary"
        :attrs="attrs"
        :on="on"
        :button-title="$t('button.reject')"
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
        :button-title="$t('button.reject')"
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
