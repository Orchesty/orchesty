<script setup lang="ts">
interface QuickFilterOption {
  value: string
  label: string
}

interface Props {
  modelValue: string
  name: string
  label?: string
  options: QuickFilterOption[]
}

const props = withDefaults(defineProps<Props>(), {
  label: 'Show only:',
})

const emit = defineEmits<{
  'update:modelValue': [value: string]
}>()

const handleChange = (value: string) => {
  emit('update:modelValue', value)
}
</script>

<template>
  <div class="flex items-center gap-3">
    <div class="hidden items-center text-sm font-medium text-gray-900 dark:text-white md:flex">
      {{ label }}
    </div>
    <div class="flex flex-wrap gap-3">
      <div v-for="option in options" :key="option.value" class="flex items-center">
        <input
          :id="`${name}-${option.value}`"
          :value="option.value"
          :checked="modelValue === option.value"
          type="radio"
          :name="name"
          @change="handleChange(option.value)"
          class="h-4 w-4 border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
        />
        <label
          :for="`${name}-${option.value}`"
          class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300"
        >
          {{ option.label }}
        </label>
      </div>
    </div>
  </div>
</template>

