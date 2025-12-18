<script setup lang="ts">
import { ref, watch, onMounted, onUnmounted } from 'vue'
import { VueDatePicker } from '@vuepic/vue-datepicker'
import '@vuepic/vue-datepicker/dist/main.css'

interface DateTimeRange {
  from: string | null
  to: string | null
}

interface Props {
  modelValue: DateTimeRange
}

const props = defineProps<Props>()

const emit = defineEmits<{
  'update:modelValue': [value: DateTimeRange]
}>()

// Convert from/to to array format for VueDatePicker range mode
const dateRange = ref<[Date, Date] | null>(null)

// Detect dark mode
const isDark = ref(false)

const updateDarkMode = () => {
  isDark.value = document.documentElement.classList.contains('dark')
}

// Initialize from props
if (props.modelValue.from && props.modelValue.to) {
  dateRange.value = [new Date(props.modelValue.from), new Date(props.modelValue.to)]
}

// Watch for external changes to modelValue
watch(
  () => props.modelValue,
  (newValue) => {
    if (newValue.from && newValue.to) {
      dateRange.value = [new Date(newValue.from), new Date(newValue.to)]
    } else {
      dateRange.value = null
    }
  },
  { deep: true }
)

// Handle date range changes from VueDatePicker
const handleDateChange = (value: [Date | null, Date | null] | null) => {
  if (!value || !value[0] || !value[1]) {
    emit('update:modelValue', {
      from: null,
      to: null,
    })
    return
  }

  // Convert dates to ISO string format for API
  emit('update:modelValue', {
    from: value[0].toISOString(),
    to: value[1].toISOString(),
  })
}

// UI Configuration for Tailwind classes
const uiConfig = {
  input: 'rounded-lg border border-gray-200 bg-white pl-9 pr-3 py-2 text-sm text-gray-900 hover:bg-gray-50 focus:border-primary-600 focus:ring-1 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:hover:bg-gray-700 dark:focus:border-primary-500 dark:focus:ring-primary-500',
  menu: 'rounded-lg border border-gray-200 bg-white shadow-lg dark:border-gray-600 dark:bg-gray-800 pb-3',
  calendar: 'px-4',
  calendarCell: 'hover:bg-gray-100 dark:hover:bg-gray-700 rounded',
  navBtnNext: 'text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700 rounded p-2',
  navBtnPrev: 'text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700 rounded p-2',
}

// Store observer reference
let darkModeObserver: MutationObserver | null = null

// Initialize and watch for dark mode changes
onMounted(() => {
  updateDarkMode()
  
  // Watch for dark mode toggle
  darkModeObserver = new MutationObserver(updateDarkMode)
  darkModeObserver.observe(document.documentElement, {
    attributes: true,
    attributeFilter: ['class'],
  })
})

onUnmounted(() => {
  if (darkModeObserver) {
    darkModeObserver.disconnect()
    darkModeObserver = null
  }
})
</script>

<template>
  <div>
    <VueDatePicker
      v-model="dateRange"
      range
      :enable-time-picker="true"
      placeholder="Select date range"
      @update:model-value="handleDateChange"
      :dark="isDark"
      
      :clearable="false"
      :ui="uiConfig"
      class="w-80"
    />
  </div>
</template>

<style scoped>
/* Hide selection preview */
:deep(.dp__selection_preview) {
  display: block;
  text-align: center;
  max-width: 100% !important;
}

/* Hide arrow */
:deep(.dp__arrow_top) {
  display: none;
}

:deep(.dp__action_row) {
  display: block;
  padding-top: 1rem;
}

/* Style action buttons */
:deep(.dp__action_buttons) {
  display: flex;
  justify-content: center;
  gap: 0.75rem;
  padding: 1rem;
}

:deep(.dp__action_button) {
  padding: 1rem 1.5rem;
  font-size: 0.875rem;
  font-weight: 500;
  border-radius: 0.5rem;
  transition: all 0.2s;
}

:deep(.dp__action_cancel) {
  background-color: white;
  border: 1px solid rgb(229, 231, 235);
  color: rgb(17, 24, 39);
}

:deep(.dp__action_cancel:hover) {
  background-color: rgb(249, 250, 251);
}

:deep(.dp__action_select) {
  background-color: rgb(37, 99, 235);
  color: white;
  border: 1px solid rgb(37, 99, 235);
}

:deep(.dp__action_select:hover) {
  background-color: rgb(29, 78, 216);
}

/* Dark mode styles for buttons - using VueDatePicker's dark class */
:deep(.dp__theme_dark .dp__action_cancel),
:deep(.dp--dark .dp__action_cancel) {
  background-color: rgb(31, 41, 55) !important;
  border-color: rgb(75, 85, 99) !important;
  color: white !important;
}

:deep(.dp__theme_dark .dp__action_cancel:hover),
:deep(.dp--dark .dp__action_cancel:hover) {
  background-color: rgb(55, 65, 81) !important;
}

:deep(.dp__theme_dark .dp__action_select),
:deep(.dp--dark .dp__action_select) {
  background-color: rgb(37, 99, 235) !important;
  border-color: rgb(37, 99, 235) !important;
}

:deep(.dp__theme_dark .dp__action_select:hover),
:deep(.dp--dark .dp__action_select:hover) {
  background-color: rgb(29, 78, 216) !important;
  border-color: rgb(29, 78, 216) !important;
}
</style>
