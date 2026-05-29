<script setup lang="ts">
import { ref, watch, computed } from 'vue'

interface Props {
  modelValue: string
  placeholder?: string
  debounce?: number
  width?: string
  mode?: 'client' | 'server'
}

const props = withDefaults(defineProps<Props>(), {
  placeholder: 'Search...',
  debounce: 300,
  width: 'w-80',
  mode: 'server',
})

const emit = defineEmits<{
  'update:modelValue': [value: string]
}>()

const localValue = ref(props.modelValue)
let debounceTimer: ReturnType<typeof setTimeout> | null = null

const hasValue = computed(() => localValue.value.length > 0)

// Watch for external changes to modelValue
watch(() => props.modelValue, (newValue) => {
  localValue.value = newValue
})

const handleInput = (event: Event) => {
  const target = event.target as HTMLInputElement
  localValue.value = target.value

  if (props.mode === 'client') {
    // Client mode: emit immediately
    emit('update:modelValue', localValue.value)
  } else {
    // Server mode: debounce before emitting
    if (debounceTimer) {
      clearTimeout(debounceTimer)
    }
    debounceTimer = setTimeout(() => {
      emit('update:modelValue', localValue.value)
    }, props.debounce)
  }
}

const handleClear = () => {
  localValue.value = ''
  if (debounceTimer) {
    clearTimeout(debounceTimer)
  }
  emit('update:modelValue', '')
}
</script>

<template>
  <div :class="width">
    <label for="search-input" class="sr-only">Search</label>
    <div class="relative">
      <!-- Search icon -->
      <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
        <svg
          class="h-5 w-5 text-gray-500 dark:text-gray-400"
          fill="currentColor"
          viewBox="0 0 20 20"
          xmlns="http://www.w3.org/2000/svg"
        >
          <path
            fill-rule="evenodd"
            d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1111.196 3.032l4.387 4.387a1 1 0 01-1.414 1.414l-4.387-4.387A6 6 0 012 8z"
            clip-rule="evenodd"
          ></path>
        </svg>
      </div>
      <input
        id="search-input"
        type="text"
        :value="localValue"
        @input="handleInput"
        :placeholder="placeholder"
        class="block w-full rounded-lg border border-gray-200 bg-white p-2 pl-10 pr-8 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
      />
      <!-- Clear button -->
      <button
        v-if="hasValue"
        type="button"
        @click="handleClear"
        class="absolute inset-y-0 right-0 flex items-center pr-2.5 text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300"
      >
        <svg
          class="h-4 w-4"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
          xmlns="http://www.w3.org/2000/svg"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M6 18L18 6M6 6l12 12"
          />
        </svg>
      </button>
    </div>
  </div>
</template>
