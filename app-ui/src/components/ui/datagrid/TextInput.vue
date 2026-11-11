<script setup lang="ts">
import { ref, watch, computed } from 'vue'

interface Props {
  modelValue: string
  placeholder?: string
  width?: string
}

const props = withDefaults(defineProps<Props>(), {
  placeholder: '',
  width: 'w-48',
})

const emit = defineEmits<{
  'update:modelValue': [value: string]
}>()

const localValue = ref(props.modelValue)
const hasValue = computed(() => localValue.value.length > 0)

// Sync external changes
watch(() => props.modelValue, (newValue) => {
  localValue.value = newValue
})

const handleInput = (event: Event) => {
  const target = event.target as HTMLInputElement
  localValue.value = target.value
}

const submitValue = () => {
  if (localValue.value !== props.modelValue) {
    emit('update:modelValue', localValue.value)
  }
}

const handleKeydown = (event: KeyboardEvent) => {
  if (event.key === 'Enter') {
    submitValue()
  }
}

const handleClear = () => {
  localValue.value = ''
  emit('update:modelValue', '')
}
</script>

<template>
  <div class="relative" :class="width">
    <input
      type="text"
      :value="localValue"
      @input="handleInput"
      @keydown="handleKeydown"
      @blur="submitValue"
      :placeholder="placeholder"
      class="block w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
      :class="{ 'pr-8': hasValue }"
    />
    <button
      v-if="hasValue"
      type="button"
      @click="handleClear"
      class="absolute inset-y-0 right-0 flex items-center pr-2.5 text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300"
    >
      <svg
        class="h-3.5 w-3.5"
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
</template>

