<script setup lang="ts">
import { computed } from 'vue'
import { cn } from '@/utils/cn'

interface Props {
  modelValue?: boolean
  label?: string
  disabled?: boolean
  id?: string
}

const props = withDefaults(defineProps<Props>(), {
  modelValue: false,
  label: '',
  disabled: false,
})

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
}>()

const checkboxId = computed(
  () => props.id || `checkbox-${Math.random().toString(36).substr(2, 9)}`,
)

const checkboxClasses = cn(
  'h-4 w-4 rounded-sm border border-gray-300 bg-gray-50',
  'focus:ring-3 focus:ring-primary-300',
  'dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600',
  'disabled:opacity-50 disabled:cursor-not-allowed',
)

const handleChange = (event: Event) => {
  const target = event.target as HTMLInputElement
  emit('update:modelValue', target.checked)
}
</script>

<template>
  <div class="flex items-start">
    <div class="flex h-5 items-center">
      <input
        :id="checkboxId"
        type="checkbox"
        :checked="modelValue"
        :disabled="disabled"
        :class="checkboxClasses"
        @change="handleChange"
      />
    </div>
    <div v-if="label" class="ml-3 text-sm">
      <label :for="checkboxId" class="text-gray-500 dark:text-gray-300">
        {{ label }}
      </label>
    </div>
  </div>
</template>

