<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import DropdownFilter from '@/components/ui/datagrid/DropdownFilter.vue'

interface ExpirationOption {
  value: string
  label: string
  days: number | null // null = no expiration
}

interface Props {
  modelValue: string | null
}

const props = defineProps<Props>()
const emit = defineEmits<{
  'update:modelValue': [value: string | null]
}>()

const expirationPeriods: ExpirationOption[] = [
  { value: 'none', label: 'Without expiration', days: null },
  { value: 'week', label: '1 week', days: 7 },
  { value: 'month', label: '1 month', days: 30 },
  { value: '3months', label: '3 months', days: 90 },
  { value: '6months', label: '6 months', days: 180 },
  { value: 'year', label: '1 year', days: 365 },
]

const selectedOption = ref<string>('none')

// Convert periods to dropdown options format
const dropdownOptions = computed(() => {
  return expirationPeriods.map(period => ({
    value: period.value,
    label: period.label
  }))
})

// Calculate expiration date based on selected option
const calculateExpirationDate = (optionValue: string): string | null => {
  const option = expirationPeriods.find(opt => opt.value === optionValue)
  if (!option || option.days === null) {
    return null
  }

  const now = new Date()
  const expirationDate = new Date(now)
  expirationDate.setDate(expirationDate.getDate() + option.days)
  expirationDate.setHours(23, 59, 59, 999)
  
  return expirationDate.toISOString()
}

// Display the calculated expiration date
const displayDate = computed(() => {
  const option = expirationPeriods.find(opt => opt.value === selectedOption.value)
  if (!option || option.days === null) {
    return 'No expiration'
  }

  const now = new Date()
  const expirationDate = new Date(now)
  expirationDate.setDate(expirationDate.getDate() + option.days)
  
  return expirationDate.toLocaleDateString('en-GB', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric'
  })
})

// Handle selection change
const handleChange = (value: string | null) => {
  if (value) {
    selectedOption.value = value
    const expirationISO = calculateExpirationDate(value)
    emit('update:modelValue', expirationISO)
  }
}

// Initialize with default value
watch(() => props.modelValue, (newValue) => {
  if (newValue === null) {
    selectedOption.value = 'none'
  }
}, { immediate: true })
</script>

<template>
  <div class="w-full">
    <label class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">
      Expiration
    </label>
    
    <!-- Dropdown Filter -->
    <DropdownFilter
      :model-value="selectedOption"
      :options="dropdownOptions"
      dropdown-id="token-expiration-dropdown"
      :full-width="true"
      @update:model-value="handleChange"
    />

    <!-- Display calculated date -->
    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
      <span v-if="selectedOption === 'none'">
        Token will never expire
      </span>
      <span v-else>
        Token will expire on: <span class="font-medium text-gray-900 dark:text-white">{{ displayDate }}</span>
      </span>
    </p>
  </div>
</template>

