<script setup lang="ts">
import { ref, watch } from 'vue'

interface Props {
  modelValue: string
  placeholder?: string
  width?: string
  type?: 'text' | 'number' | 'email' | 'password'
  id?: string
  disabled?: boolean
  required?: boolean
  error?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  placeholder: '',
  width: 'w-full',
  type: 'text',
  id: undefined,
  disabled: false,
  required: false,
  error: false,
})

const emit = defineEmits<{
  'update:modelValue': [value: string]
}>()

const localValue = ref(props.modelValue)

// Sync external changes
watch(() => props.modelValue, (newValue) => {
  localValue.value = newValue
})

const handleInput = (event: Event) => {
  const target = event.target as HTMLInputElement
  localValue.value = target.value
  emit('update:modelValue', target.value)
}
</script>

<template>
  <input
    :id="id"
    :type="type"
    :value="localValue"
    :placeholder="placeholder"
    :disabled="disabled"
    :required="required"
    @input="handleInput"
    :class="[
      width,
      'rounded-lg border bg-gray-50 px-3 py-2.5 text-sm text-gray-900 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400',
      error
        ? 'border-red-500 focus:border-red-500 focus:ring-red-500 dark:border-red-500 dark:focus:border-red-500 dark:focus:ring-red-500'
        : 'border-gray-300 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:focus:border-primary-500 dark:focus:ring-primary-500',
      { 'cursor-not-allowed opacity-50': disabled }
    ]"
  />
</template>
