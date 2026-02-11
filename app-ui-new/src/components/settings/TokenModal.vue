<script setup lang="ts">
import { ref, watch } from 'vue'
import Modal from '@/components/ui/Modal.vue'
import TextInput from '@/components/ui/datagrid/TextInput.vue'
import Button from '@/components/ui/Button.vue'
import TokenExpirationSelect from '@/components/settings/TokenExpirationSelect.vue'
import type { TokenScope } from '@/types/settings'

interface Props {
  modelValue: boolean
  availableScopes: TokenScope[]
}

const props = defineProps<Props>()
const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  generate: [data: { name: string; expiration: string | null; scopes: string[] }]
}>()

// Form state
const formData = ref({
  name: '',
  expiration: null as string | null,
  scopes: [] as string[],
})

// Validation
const nameError = ref('')
const scopesError = ref('')

// Initialize form when modal opens
watch(
  () => props.modelValue,
  (isOpen) => {
    if (isOpen) {
      formData.value = {
        name: '',
        expiration: null,
        scopes: [],
      }
      // Reset validation
      nameError.value = ''
      scopesError.value = ''
    }
  }
)

const toggleScope = (scopeId: string) => {
  const index = formData.value.scopes.indexOf(scopeId)
  if (index > -1) {
    formData.value.scopes.splice(index, 1)
  } else {
    formData.value.scopes.push(scopeId)
  }
}

const isScopeSelected = (scopeId: string) => {
  return formData.value.scopes.includes(scopeId)
}

const validate = (): boolean => {
  let isValid = true

  if (!formData.value.name.trim()) {
    nameError.value = 'Name is required'
    isValid = false
  } else {
    nameError.value = ''
  }

  if (formData.value.scopes.length === 0) {
    scopesError.value = 'At least one scope is required'
    isValid = false
  } else {
    scopesError.value = ''
  }

  return isValid
}

const handleGenerate = () => {
  if (!validate()) {
    return
  }

  emit('generate', {
    name: formData.value.name,
    expiration: formData.value.expiration,
    scopes: formData.value.scopes,
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
    id="token-modal"
    title="Create Token"
    size="md"
  >
    <form @submit.prevent="handleGenerate" class="space-y-4">
      <!-- Name -->
      <div>
        <label for="token-name" class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">
          Name
          <span class="text-red-600 dark:text-red-400">*</span>
        </label>
        <TextInput
          id="token-name"
          v-model="formData.name"
          placeholder="Enter token name"
          width="w-full"
        />
        <p v-if="nameError" class="mt-1 text-sm text-red-600 dark:text-red-400">
          {{ nameError }}
        </p>
      </div>

      <!-- Expiration -->
      <TokenExpirationSelect v-model="formData.expiration"/>

      <!-- Scopes -->
      <div>
        <label class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">
          Scopes
          <span class="text-red-600 dark:text-red-400">*</span>
        </label>
        <div class="space-y-2">
          <div v-for="scope in availableScopes" :key="scope.id" class="flex items-center">
            <input
              :id="`scope-${scope.id}`"
              type="checkbox"
              :checked="isScopeSelected(scope.id)"
              @change="toggleScope(scope.id)"
              class="h-4 w-4 rounded border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
            />
            <label
              :for="`scope-${scope.id}`"
              class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300"
            >
              {{ scope.label }}
            </label>
          </div>
        </div>
        <p v-if="scopesError" class="mt-1 text-sm text-red-600 dark:text-red-400">
          {{ scopesError }}
        </p>
      </div>
    </form>

    <template #footer-actions>
      <Button variant="outline" @click="handleClose">Cancel</Button>
      <Button variant="primary" @click="handleGenerate">Generate Token</Button>
    </template>
  </Modal>
</template>

