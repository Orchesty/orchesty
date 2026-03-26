<script setup lang="ts">
import { ref, watch } from 'vue'
import Modal from '@/components/ui/Modal.vue'
import Button from '@/components/ui/Button.vue'
import Textarea from '@/components/ui/datagrid/Textarea.vue'
import { updateTopology } from '@/services/topologiesService'
import { useToast } from '@/composables/useToast'

interface Props {
  modelValue: boolean
  topologyId: string
  topologyName?: string
  currentDescription?: string
}

const props = withDefaults(defineProps<Props>(), {
  topologyName: '',
  currentDescription: '',
})

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  saved: []
}>()

const { showToast } = useToast()

const description = ref('')
const saving = ref(false)

// Reset when modal opens
watch(
  () => props.modelValue,
  (newValue) => {
    if (newValue) {
      description.value = props.currentDescription
    }
  },
)

const handleClose = () => {
  emit('update:modelValue', false)
}

const handleSave = async () => {
  saving.value = true
  try {
    await updateTopology(props.topologyId, { description: description.value })
    showToast('Topology updated successfully', 'success')
    emit('saved')
    handleClose()
  } catch (error) {
    console.error('Failed to update topology:', error)
    showToast('Failed to update topology', 'error')
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <Modal
    :model-value="modelValue"
    id="edit-topology-modal"
    :title="`Edit ${topologyName || 'Topology'}`"
    size="md"
    @update:model-value="emit('update:modelValue', $event)"
  >
    <form @submit.prevent="handleSave" class="space-y-4">
      <div>
        <label for="topology-description" class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">
          Description
        </label>
        <Textarea
          id="topology-description"
          v-model="description"
          placeholder="Enter topology description"
          :rows="5"
        />
      </div>
    </form>

    <template #footer-actions>
      <Button variant="outline" @click="handleClose">
        Cancel
      </Button>
      <Button variant="primary" :disabled="saving" @click="handleSave">
        {{ saving ? 'Saving...' : 'Save' }}
      </Button>
    </template>
  </Modal>
</template>
