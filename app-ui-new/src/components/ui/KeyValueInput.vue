<script setup lang="ts">
import { ref, watch } from 'vue'

interface Props {
  modelValue: Record<string, string>
}

const props = defineProps<Props>()
const emit = defineEmits<{
  'update:modelValue': [value: Record<string, string>]
}>()

// Convert object to array of key-value pairs for easier manipulation
interface KeyValuePair {
  key: string
  value: string
}

const pairs = ref<KeyValuePair[]>([])

// Initialize pairs from modelValue
const initializePairs = () => {
  const entries = Object.entries(props.modelValue)
  if (entries.length === 0) {
    // Start with one empty pair
    pairs.value = [{ key: '', value: '' }]
  } else {
    pairs.value = entries.map(([key, value]) => ({ key, value }))
  }
}

initializePairs()

// Watch for external changes to modelValue
watch(
  () => props.modelValue,
  () => {
    initializePairs()
  },
  { deep: true }
)

// Add a new empty pair
const addPair = () => {
  pairs.value.push({ key: '', value: '' })
}

// Remove a pair at the given index
const removePair = (index: number) => {
  pairs.value.splice(index, 1)
  // Ensure at least one pair remains
  if (pairs.value.length === 0) {
    pairs.value.push({ key: '', value: '' })
  }
  emitUpdate()
}

// Update a pair's key
const updateKey = (index: number, key: string) => {
  const pair = pairs.value[index]
  if (!pair) return
  pair.key = key
  emitUpdate()
}

// Update a pair's value
const updateValue = (index: number, value: string) => {
  const pair = pairs.value[index]
  if (!pair) return
  pair.value = value
  emitUpdate()
}

// Emit the updated object
const emitUpdate = () => {
  const obj: Record<string, string> = {}
  pairs.value.forEach((pair) => {
    if (pair.key.trim()) {
      obj[pair.key] = pair.value
    }
  })
  emit('update:modelValue', obj)
}
</script>

<template>
  <div>
    <div class="flex items-center justify-between mb-2">
      <label class="block text-sm font-medium text-gray-900 dark:text-white">Headers</label>
      <button
        type="button"
        @click="addPair"
        class="text-sm text-primary-600 hover:text-primary-700 dark:text-primary-500 dark:hover:text-primary-400"
      >
        + Add Header
      </button>
    </div>
    <div class="space-y-2">
      <div
        v-for="(pair, index) in pairs"
        :key="index"
        class="flex gap-2 items-start"
      >
        <div class="flex-1">
          <input
            type="text"
            :value="pair.key"
            @input="updateKey(index, ($event.target as HTMLInputElement).value)"
            placeholder="Key"
            class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
          />
        </div>
        <div class="flex-1">
          <input
            type="text"
            :value="pair.value"
            @input="updateValue(index, ($event.target as HTMLInputElement).value)"
            placeholder="Value"
            class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
          />
        </div>
        <button
          type="button"
          @click="removePair(index)"
          class="mt-2 text-red-600 hover:text-red-700 dark:text-red-500 dark:hover:text-red-400"
        >
          <svg
            class="w-5 h-5"
            fill="currentColor"
            viewBox="0 0 20 20"
            xmlns="http://www.w3.org/2000/svg"
          >
            <path
              fill-rule="evenodd"
              d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
              clip-rule="evenodd"
            />
          </svg>
        </button>
      </div>
    </div>
  </div>
</template>

