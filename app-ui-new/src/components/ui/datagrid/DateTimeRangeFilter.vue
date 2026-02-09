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
  input: 'rounded-lg border border-gray-200 bg-white pl-9 pr-3 py-2 text-sm text-gray-900 placeholder-gray-500 hover:bg-gray-50 focus:border-primary-600 focus:ring-1 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:placeholder-gray-400 dark:hover:bg-gray-700 dark:focus:border-primary-500 dark:focus:ring-primary-500',
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
/* VueDatePicker theme overrides — light mode */
:deep(.dp__theme_light) {
  --dp-primary-color: #10C86C;
  --dp-primary-text-color: #ffffff;
  --dp-background-color: #ffffff;
  --dp-text-color: #141414;
  --dp-border-color: #C4C4C4;
  --dp-menu-border-color: #C4C4C4;
  --dp-hover-color: #F8F8F8;
  --dp-hover-text-color: #141414;
  --dp-placeholder-color: #929292;
  --dp-range-between-dates-background-color: #D3FBDF;
  --dp-range-between-dates-text-color: #0D663C;
  --dp-range-between-border-color: #D3FBDF;
  --dp-icon-color: #929292;
  --dp-secondary-color: #ABABAB;
}

/* VueDatePicker theme overrides — dark mode */
:deep(.dp__theme_dark) {
  --dp-primary-color: #1BEA83;
  --dp-primary-text-color: #141414;
  --dp-background-color: #1F1F1F;
  --dp-text-color: #F8F8F8;
  --dp-border-color: #636363;
  --dp-menu-border-color: #636363;
  --dp-hover-color: #333333;
  --dp-hover-text-color: #F8F8F8;
  --dp-placeholder-color: #929292;
  --dp-range-between-dates-background-color: #043A21;
  --dp-range-between-dates-text-color: #ABF7C5;
  --dp-range-between-border-color: #043A21;
  --dp-icon-color: #929292;
  --dp-secondary-color: #7A7A7A;
}

/* Force placeholder color to match SearchInput */
:deep(.dp__input::placeholder) {
  color: #929292 !important;
  opacity: 1;
}

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
  border-radius: 9999px;
  transition: all 0.2s;
}

:deep(.dp__action_cancel) {
  background-color: #ffffff;
  border: 1px solid #C4C4C4;
  color: #141414;
}

:deep(.dp__action_cancel:hover) {
  background-color: #F8F8F8;
}

:deep(.dp__action_select) {
  background-color: #10C86C;
  color: #ffffff;
  border: 1px solid #10C86C;
}

:deep(.dp__action_select:hover) {
  background-color: #0D9E58;
  border-color: #0D9E58;
}

/* Dark mode styles for buttons */
:deep(.dp__theme_dark .dp__action_cancel),
:deep(.dp--dark .dp__action_cancel) {
  background-color: #333333 !important;
  border-color: #636363 !important;
  color: #F8F8F8 !important;
}

:deep(.dp__theme_dark .dp__action_cancel:hover),
:deep(.dp--dark .dp__action_cancel:hover) {
  background-color: #4D4D4D !important;
}

:deep(.dp__theme_dark .dp__action_select),
:deep(.dp--dark .dp__action_select) {
  background-color: #1BEA83 !important;
  border-color: #1BEA83 !important;
  color: #141414 !important;
}

:deep(.dp__theme_dark .dp__action_select:hover),
:deep(.dp--dark .dp__action_select:hover) {
  background-color: #10C86C !important;
  border-color: #10C86C !important;
}
</style>
