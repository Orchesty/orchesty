<script setup lang="ts">
import { computed } from 'vue'
import { cn } from '@/utils/cn'

type InputType = 'text' | 'email' | 'password' | 'number'

interface Props {
  type?: InputType
  label?: string
  placeholder?: string
  modelValue?: string | number
  required?: boolean
  disabled?: boolean
  id?: string
}

const props = withDefaults(defineProps<Props>(), {
  type: 'text',
  label: '',
  placeholder: '',
  modelValue: '',
  required: false,
  disabled: false,
})

const emit = defineEmits<{
  'update:modelValue': [value: string | number]
}>()

const inputId = computed(() => props.id || `input-${Math.random().toString(36).substr(2, 9)}`)

const inputClasses = cn(
  'block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-gray-900',
  'focus:border-primary-600 focus:ring-primary-600',
  'dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-400',
  'dark:focus:border-primary-500 dark:focus:ring-primary-500',
  'sm:text-sm',
  'disabled:opacity-50 disabled:cursor-not-allowed',
)

const handleInput = (event: Event) => {
  const target = event.target as HTMLInputElement
  emit('update:modelValue', target.value)
}
</script>

<template>
  <div>
    <label
      v-if="label"
      :for="inputId"
      class="mb-2 block text-sm font-medium text-gray-900 dark:text-white"
    >
      {{ label }}
    </label>
    <input
      :id="inputId"
      :type="type"
      :value="modelValue"
      :placeholder="placeholder"
      :required="required"
      :disabled="disabled"
      :class="inputClasses"
      @input="handleInput"
    />
  </div>
</template>

