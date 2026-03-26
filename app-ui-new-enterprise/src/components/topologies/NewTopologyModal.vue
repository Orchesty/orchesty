<script setup lang="ts">
import { ref, watch, nextTick } from 'vue'
import Modal from '@/components/ui/Modal.vue'
import Button from '@/components/ui/Button.vue'
import FormInput from '@/components/ui/FormInput.vue'
import { createTopology } from '@/services/topologiesService'
import { useToast } from '@/composables/useToast'

interface Props {
  modelValue: boolean
  categoryId?: string | null
}

const props = withDefaults(defineProps<Props>(), {
  categoryId: null,
})

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  'created': [topologyId: string]
}>()

const { showToast } = useToast()

const formData = ref({
  name: ''
})
const saving = ref(false)

const handleClose = () => {
  emit('update:modelValue', false)
  formData.value = { name: '' }
}

const handleCreate = async () => {
  if (!formData.value.name.trim()) return

  saving.value = true
  try {
    const result = await createTopology(formData.value.name.trim(), props.categoryId ?? null)
    showToast('Topology created successfully', 'success')
    emit('created', result._id)
    handleClose()
  } catch (error) {
    console.error('Failed to create topology:', error)
    showToast('Failed to create topology', 'error')
  } finally {
    saving.value = false
  }
}

const handleShown = () => {
  nextTick(() => {
    document.getElementById('topology-name')?.focus()
  })
}

watch(() => props.modelValue, (newValue) => {
  if (!newValue) {
    formData.value = { name: '' }
  }
})
</script>

<template>
  <Modal
    :model-value="modelValue"
    id="new-topology-modal"
    title="New Topology"
    size="md"
    @update:model-value="emit('update:modelValue', $event)"
    @shown="handleShown"
  >
    <form @submit.prevent="handleCreate" class="space-y-4">
      <!-- Name -->
      <div>
        <label for="topology-name" class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">
          Name
          <span class="text-red-600 dark:text-red-400">*</span>
        </label>
        <FormInput
          id="topology-name"
          v-model="formData.name"
          placeholder="Enter topology name"
        />
      </div>
    </form>

    <template #footer-actions>
      <Button variant="outline" @click="handleClose">
        Cancel
      </Button>
      <Button variant="primary" :disabled="saving || !formData.name.trim()" @click="handleCreate">
        {{ saving ? 'Creating...' : 'Create Topology' }}
      </Button>
    </template>
  </Modal>
</template>
