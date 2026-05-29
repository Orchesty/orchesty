<script setup lang="ts">
import { ref, computed } from 'vue'
import Modal from '@/components/ui/Modal.vue'
import Button from '@/components/ui/Button.vue'
import EditMessageForm from './EditMessageForm.vue'
import type { TrashItem } from '@/types/trash'

interface Props {
  modelValue: boolean
  item: TrashItem
}

defineProps<Props>()

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  save: [data: { headers: Record<string, unknown>; body: Record<string, unknown> }]
  'save-and-approve': [data: { headers: Record<string, unknown>; body: Record<string, unknown> }]
}>()

const formRef = ref<InstanceType<typeof EditMessageForm> | null>(null)
const isFormValid = computed(() => formRef.value?.isValid ?? false)

const handleSave = () => {
  const data = formRef.value?.getPayload()
  if (data) emit('save', data)
}

const handleSaveAndApprove = () => {
  const data = formRef.value?.getPayload()
  if (data) emit('save-and-approve', data)
}
</script>

<template>
  <Modal
    :model-value="modelValue"
    id="edit-message-modal"
    title="Update message"
    size="xl"
    @update:model-value="$emit('update:modelValue', $event)"
  >
    <EditMessageForm ref="formRef" :item="item" />

    <template #footer-actions>
      <div class="flex items-center justify-end gap-3">
        <Button variant="outline" @click="$emit('update:modelValue', false)">
          Close
        </Button>
        <Button variant="primary" :disabled="!isFormValid" @click="handleSave">
          Save
        </Button>
        <Button variant="primary" :disabled="!isFormValid" @click="handleSaveAndApprove">
          Save and Approve
        </Button>
      </div>
    </template>
  </Modal>
</template>
