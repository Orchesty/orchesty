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
        <sub-heading>{{ $t('trashModal.body') }}</sub-heading>
      </div>
    </template>
    <template #actions>
      <base-button
        :button-title="$t('button.reject')"
        :on-click="rejectTrashItem"
      />
      <base-button
        :button-title="$t('button.cancel')"
        :on-click="(isOpen = false)"
      />
    </template>
  </base-modal>
</template>

<script>
import BaseModal from '@/components/commons/BaseModal'
import BaseButton from '@/components/commons/BaseButton'
import { callApi } from '@/utils/apiFetch'
import { API } from '@/api'
import SubHeading from '@/components/commons/SubHeading'
export default {
  name: 'TrashRejectModal',
  components: { SubHeading, BaseButton, BaseModal },
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
