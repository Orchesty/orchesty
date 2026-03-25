<script setup lang="ts">
import { computed } from 'vue'
import type { TimeFilter } from '@/types/dashboard'

interface Props {
  modelValue: TimeFilter
}

const props = defineProps<Props>()

const emit = defineEmits<{
  'update:modelValue': [value: TimeFilter]
}>()

const filters: { value: TimeFilter; label: string; title: string }[] = [
  { value: '1h', label: '1h', title: '1 hour view' },
  { value: '24h', label: '24h', title: '24 hours view' },
  { value: '7d', label: '7d', title: '7 days view' },
  { value: '30d', label: '30d', title: '30 days view' },
]

const getTimeFilterClasses = (filter: TimeFilter) => {
  const isActive = props.modelValue === filter
  
  const baseClasses = 'fc-button fc-button-primary px-3 py-2 text-sm font-medium focus:outline-hidden'
  
  const activeClasses = isActive
    ? 'bg-primary-700 text-white border border-primary-700 hover:bg-primary-800 dark:bg-primary-600 dark:border-primary-600 dark:hover:bg-primary-700'
    : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50 hover:text-primary-700 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700 dark:hover:text-white'
  
  const borderClasses = filter === '1h' 
    ? 'rounded-l-full' 
    : filter === '30d' 
      ? 'rounded-r-full border-l-0' 
      : 'border-l-0'
  
  return `${baseClasses} ${activeClasses} ${borderClasses}`
}

const handleFilterChange = (filter: TimeFilter) => {
  emit('update:modelValue', filter)
}
</script>

<template>
  <div class="inline-flex">
    <button
      v-for="filter in filters"
      :key="filter.value"
      type="button"
      :title="filter.title"
      :aria-pressed="modelValue === filter.value"
      :class="getTimeFilterClasses(filter.value)"
      @click="handleFilterChange(filter.value)"
    >
      {{ filter.label }}
    </button>
  </div>
</template>

