<script setup lang="ts">
import { ref, watch } from 'vue'
import type { AuditEntityAttribute } from '@/types/settings'

interface Props {
  modelValue: AuditEntityAttribute[]
}

const props = defineProps<Props>()
const emit = defineEmits<{
  'update:modelValue': [value: AuditEntityAttribute[]]
}>()

const attributes = ref<AuditEntityAttribute[]>([])

// Initialize attributes from modelValue
const initializeAttributes = () => {
  if (props.modelValue.length === 0) {
    // Start with one empty attribute
    attributes.value = [{ name: '', description: '' }]
  } else {
    attributes.value = [...props.modelValue]
  }
}

initializeAttributes()

// Watch for external changes to modelValue
watch(
  () => props.modelValue,
  () => {
    initializeAttributes()
  },
  { deep: true }
)

// Add a new empty attribute
const addAttribute = () => {
  attributes.value.push({ name: '', description: '' })
}

// Remove an attribute at the given index
const removeAttribute = (index: number) => {
  attributes.value.splice(index, 1)
  // Ensure at least one attribute remains
  if (attributes.value.length === 0) {
    attributes.value.push({ name: '', description: '' })
  }
  emitUpdate()
}

// Update an attribute's name
const updateName = (index: number, name: string) => {
  const attr = attributes.value[index]
  if (!attr) return
  attr.name = name
  emitUpdate()
}

// Update an attribute's description
const updateDescription = (index: number, description: string) => {
  const attr = attributes.value[index]
  if (!attr) return
  attr.description = description
  emitUpdate()
}

// Emit the updated array
const emitUpdate = () => {
  // Filter out empty attributes
  const filtered = attributes.value.filter(
    (attr) => attr.name.trim() || attr.description.trim()
  )
  emit('update:modelValue', filtered)
}
</script>

<template>
  <div>
    <div class="flex items-center justify-between mb-2">
      <label class="block text-sm font-medium text-gray-900 dark:text-white">Attributes</label>
      <button
        type="button"
        @click="addAttribute"
        class="text-sm text-primary-600 hover:text-primary-700 dark:text-primary-500 dark:hover:text-primary-400"
      >
        + Add Attribute
      </button>
    </div>
    <div class="space-y-3">
      <div
        v-for="(attr, index) in attributes"
        :key="index"
        class="relative space-y-3 rounded-lg border border-gray-200 p-4 dark:border-gray-600"
      >
        <button
          type="button"
          @click="removeAttribute(index)"
          class="absolute right-3 top-3 text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300"
          title="Remove attribute"
        >
          <svg
            class="h-4 w-4"
            fill="none"
            stroke="currentColor"
            stroke-width="1.5"
            viewBox="0 0 24 24"
            aria-hidden="true"
          >
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6m0 12L6 6" />
          </svg>
          <span class="sr-only">Remove attribute</span>
        </button>
        <div>
          <label class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-400">
            Attribute name
          </label>
          <input
            type="text"
            :value="attr.name"
            @input="updateName(index, ($event.target as HTMLInputElement).value)"
            placeholder="Identifier"
            class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
          />
        </div>
        <div>
          <label class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-400">
            Description
          </label>
          <textarea
            :value="attr.description"
            @input="updateDescription(index, ($event.target as HTMLTextAreaElement).value)"
            rows="3"
            placeholder="Describe the attribute"
            class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
          ></textarea>
        </div>
      </div>
    </div>
  </div>
</template>

