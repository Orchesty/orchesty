<script setup lang="ts">
import { ref, watch } from 'vue'
import Modal from '@/components/ui/Modal.vue'
import FormInput from '@/components/ui/FormInput.vue'
import AttributeInput from '@/components/ui/AttributeInput.vue'
import Button from '@/components/ui/Button.vue'
import type { AuditEntity, AuditEntityAttribute } from '@/types/settings'

interface Props {
  modelValue: boolean
  entity: AuditEntity | null
  mode: 'create' | 'edit'
}

const props = defineProps<Props>()
const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  save: [data: Omit<AuditEntity, 'id'> | Partial<AuditEntity>]
}>()

// Form state
const formData = ref({
  name: '',
  attributes: [] as AuditEntityAttribute[],
})

// Validation
const nameError = ref('')
const attributesError = ref('')

// Initialize form when entity changes or modal opens
watch(
  [() => props.entity, () => props.modelValue],
  () => {
    if (props.modelValue) {
      if (props.entity && props.mode === 'edit') {
        formData.value = {
          name: props.entity.name,
          attributes: [...props.entity.attributes],
        }
      } else {
        formData.value = {
          name: '',
          attributes: [],
        }
      }
      // Reset validation
      nameError.value = ''
      attributesError.value = ''
    }
  },
  { immediate: true }
)

const validate = (): boolean => {
  let isValid = true

  if (!formData.value.name.trim()) {
    nameError.value = 'Name is required'
    isValid = false
  } else {
    nameError.value = ''
  }

  if (formData.value.attributes.length === 0) {
    attributesError.value = 'At least one attribute is required'
    isValid = false
  } else {
    attributesError.value = ''
  }

  return isValid
}

const handleSave = () => {
  if (!validate()) {
    return
  }

  emit('save', {
    name: formData.value.name,
    attributes: formData.value.attributes,
  })
}

const handleClose = () => {
  emit('update:modelValue', false)
}
</script>

<template>
  <Modal
    :model-value="modelValue"
    @update:model-value="$emit('update:modelValue', $event)"
    id="entity-modal"
    :title="mode === 'create' ? 'Add Entity' : 'Edit Entity'"
    size="md"
  >
    <form @submit.prevent="handleSave" class="space-y-4">
      <!-- Name -->
      <div>
        <label for="entity-name" class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">
          Name
          <span class="text-red-600 dark:text-red-400">*</span>
        </label>
        <FormInput
          id="entity-name"
          v-model="formData.name"
          placeholder="Enter entity name"
        />
        <p v-if="nameError" class="mt-1 text-sm text-red-600 dark:text-red-400">
          {{ nameError }}
        </p>
      </div>

      <!-- Attributes -->
      <div>
        <AttributeInput v-model="formData.attributes" />
        <p v-if="attributesError" class="mt-1 text-sm text-red-600 dark:text-red-400">
          {{ attributesError }}
        </p>
      </div>
    </form>

    <template #footer-actions>
      <Button variant="outline" @click="handleClose">Cancel</Button>
      <Button variant="primary" @click="handleSave">Save Entity</Button>
    </template>
  </Modal>
</template>

