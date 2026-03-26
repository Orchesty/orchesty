<script setup lang="ts">
import { ref, computed, onMounted, nextTick } from 'vue'
import { useDateFormat } from '@/composables/useDateFormat'

const { formatDate } = useDateFormat()

interface TimeRangeOption {
  value: string
  label: string
}

interface Props {
  modelValue: string
  options?: TimeRangeOption[]
}

const props = withDefaults(defineProps<Props>(), {
  options: () => [
    { value: 'yesterday', label: 'Yesterday' },
    { value: 'today', label: 'Today' },
    { value: 'last-7-days', label: 'Last 7 days' },
    { value: 'last-30-days', label: 'Last 30 days' },
    { value: 'last-90-days', label: 'Last 90 days' },
    { value: 'this-month', label: 'This month' },
  ],
})

const emit = defineEmits<{
  'update:modelValue': [value: string]
}>()

// Generate unique ID for dropdown
const dropdownId = ref(`time-range-filter-${Math.random().toString(36).substr(2, 9)}`)

// eslint-disable-next-line @typescript-eslint/no-explicit-any
const dropdownInstance = ref<any>(null)

// Datepicker refs
const startDateInput = ref<HTMLInputElement | null>(null)
const endDateInput = ref<HTMLInputElement | null>(null)

// Format date range for display
const formatDateRange = (startStr: string, endStr: string): string => {
  return `${formatDate(startStr)} - ${formatDate(endStr)}`
}

// Get current label
const currentLabel = computed(() => {
  if (props.modelValue.startsWith('custom:')) {
    // Parse custom:YYYY-MM-DD:YYYY-MM-DD format
    const parts = props.modelValue.split(':')
    if (parts.length === 3 && parts[1] && parts[2]) {
      return formatDateRange(parts[1], parts[2])
    }
  }
  const option = props.options.find((opt) => opt.value === props.modelValue)
  return option?.label || 'Select period'
})

// Watch for date range changes from Flowbite datepicker
const checkDateRangeInputs = () => {
  if (startDateInput.value?.value && endDateInput.value?.value) {
    const start = startDateInput.value.value
    const end = endDateInput.value.value
    
    // Emit custom value format: "custom:YYYY-MM-DD:YYYY-MM-DD"
    emit('update:modelValue', `custom:${start}:${end}`)
    
    // Close dropdown and datepickers
    if (dropdownInstance.value) {
      dropdownInstance.value.hide()
    }
    closeDatepickers()
    
    // Clear inputs for next use
    startDateInput.value.value = ''
    endDateInput.value.value = ''
  }
}

const handleSelect = (value: string) => {
  emit('update:modelValue', value)
  
  // Close dropdown and datepickers
  if (dropdownInstance.value) {
    dropdownInstance.value.hide()
  }
  
  // Close any open datepicker calendars
  closeDatepickers()
}

const closeDatepickers = () => {
  // Hide all datepicker popups
  document.querySelectorAll('.datepicker').forEach((picker) => {
    if (picker instanceof HTMLElement) {
      picker.style.display = 'none'
    }
  })
}

onMounted(async () => {
  await nextTick()
  
  const dropdownElement = document.getElementById(dropdownId.value)
  const buttonElement = document.getElementById(`${dropdownId.value}-button`)
  
  if (dropdownElement && buttonElement) {
    const { Dropdown } = await import('flowbite')
    
    dropdownInstance.value = new Dropdown(dropdownElement, buttonElement, {
      placement: 'bottom',
      triggerType: 'click',
      offsetSkidding: 0,
      offsetDistance: 10,
      onHide: () => {
        // Close datepickers when dropdown closes
        closeDatepickers()
      },
    })
  }
  
  // Flowbite automatically initializes DateRangePicker via date-rangepicker attribute
  // Watch for changes in date inputs
  if (startDateInput.value) {
    startDateInput.value.addEventListener('changeDate', checkDateRangeInputs)
  }
  
  if (endDateInput.value) {
    endDateInput.value.addEventListener('changeDate', checkDateRangeInputs)
  }
})
</script>

<template>
  <div>
    <button
      :id="`${dropdownId}-button`"
      :data-dropdown-toggle="dropdownId"
      data-dropdown-ignore-click-outside-class="datepicker"
      class="flex items-center justify-center whitespace-nowrap rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 focus:z-10 focus:outline-hidden dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
      type="button"
    >
      <svg
        class="-ms-0.5 me-1.5 h-4 w-4"
        aria-hidden="true"
        xmlns="http://www.w3.org/2000/svg"
        fill="currentColor"
        viewBox="0 0 24 24"
      >
        <path
          fill-rule="evenodd"
          d="M5 5c.6 0 1-.4 1-1a1 1 0 1 1 2 0c0 .6.4 1 1 1h1c.6 0 1-.4 1-1a1 1 0 1 1 2 0c0 .6.4 1 1 1h1c.6 0 1-.4 1-1a1 1 0 1 1 2 0c0 .6.4 1 1 1a2 2 0 0 1 2 2v1c0 .6-.4 1-1 1H4a1 1 0 0 1-1-1V7c0-1.1.9-2 2-2ZM3 19v-7c0-.6.4-1 1-1h16c.6 0 1 .4 1 1v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2Zm6-6c0-.6-.4-1-1-1a1 1 0 1 0 0 2c.6 0 1-.4 1-1Zm2 0a1 1 0 1 1 2 0c0 .6-.4 1-1 1a1 1 0 0 1-1-1Zm6 0c0-.6-.4-1-1-1a1 1 0 1 0 0 2c.6 0 1-.4 1-1ZM7 17a1 1 0 1 1 2 0c0 .6-.4 1-1 1a1 1 0 0 1-1-1Zm6 0c0-.6-.4-1-1-1a1 1 0 1 0 0 2c.6 0 1-.4 1-1Zm2 0a1 1 0 1 1 2 0c0 .6-.4 1-1 1a1 1 0 0 1-1-1Z"
          clip-rule="evenodd"
        ></path>
      </svg>
      {{ currentLabel }}
      <svg
        class="ms-1.5 h-4 w-4"
        aria-hidden="true"
        xmlns="http://www.w3.org/2000/svg"
        fill="none"
        viewBox="0 0 24 24"
      >
        <path
          stroke="currentColor"
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="m19 9-7 7-7-7"
        ></path>
      </svg>
    </button>

    <!-- Dropdown Menu -->
    <div
      :id="dropdownId"
      class="z-50 my-4 hidden w-80 list-none divide-y divide-gray-100 rounded-lg bg-white text-sm font-medium shadow-xs dark:divide-gray-600 dark:bg-gray-700"
    >
      <ul class="p-2 text-gray-500 dark:text-gray-400" role="none">
        <li v-for="option in options" :key="option.value">
          <button
            type="button"
            class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
            :class="{
              'bg-gray-100 text-gray-900 dark:bg-gray-600 dark:text-white':
                modelValue === option.value,
            }"
            role="menuitem"
            @click="handleSelect(option.value)"
          >
            {{ option.label }}
          </button>
        </li>
      </ul>
      <!-- Custom Period Section -->
      <div class="p-5">
        <span class="mb-2 block text-gray-900 dark:text-white">Custom period:</span>
        <div id="date-range-picker" date-rangepicker class="flex w-full items-center gap-3">
          <div class="relative w-full">
            <div class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3">
              <svg
                class="h-4 w-4 text-gray-500 dark:text-gray-400"
                aria-hidden="true"
                xmlns="http://www.w3.org/2000/svg"
                width="24"
                height="24"
                fill="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  fill-rule="evenodd"
                  d="M5 5a1 1 0 0 0 1-1 1 1 0 1 1 2 0 1 1 0 0 0 1 1h1a1 1 0 0 0 1-1 1 1 0 1 1 2 0 1 1 0 0 0 1 1h1a1 1 0 0 0 1-1 1 1 0 1 1 2 0 1 1 0 0 0 1 1 2 2 0 0 1 2 2v1a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V7a2 2 0 0 1 2-2ZM3 19v-7a1 1 0 0 1 1-1h16a1 1 0 0 1 1 1v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2Zm6.01-6a1 1 0 1 0-2 0 1 1 0 0 0 2 0Zm2 0a1 1 0 1 1 2 0 1 1 0 0 1-2 0Zm6 0a1 1 0 1 0-2 0 1 1 0 0 0 2 0Zm-10 4a1 1 0 1 1 2 0 1 1 0 0 1-2 0Zm6 0a1 1 0 1 0-2 0 1 1 0 0 0 2 0Zm2 0a1 1 0 1 1 2 0 1 1 0 0 1-2 0Z"
                  clip-rule="evenodd"
                />
              </svg>
            </div>
            <input
              id="datepicker-range-start"
              ref="startDateInput"
              name="start"
              type="text"
              class="w-full rounded-lg border border-gray-300 bg-gray-50 p-2 ps-9 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
              placeholder="Start date"
            />
          </div>
          <div class="relative w-full">
            <div class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3">
              <svg
                class="h-4 w-4 text-gray-500 dark:text-gray-400"
                aria-hidden="true"
                xmlns="http://www.w3.org/2000/svg"
                width="24"
                height="24"
                fill="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  fill-rule="evenodd"
                  d="M5 5a1 1 0 0 0 1-1 1 1 0 1 1 2 0 1 1 0 0 0 1 1h1a1 1 0 0 0 1-1 1 1 0 1 1 2 0 1 1 0 0 0 1 1h1a1 1 0 0 0 1-1 1 1 0 1 1 2 0 1 1 0 0 0 1 1 2 2 0 0 1 2 2v1a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V7a2 2 0 0 1 2-2ZM3 19v-7a1 1 0 0 1 1-1h16a1 1 0 0 1 1 1v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2Zm6.01-6a1 1 0 1 0-2 0 1 1 0 0 0 2 0Zm2 0a1 1 0 1 1 2 0 1 1 0 0 1-2 0Zm6 0a1 1 0 1 0-2 0 1 1 0 0 0 2 0Zm-10 4a1 1 0 1 1 2 0 1 1 0 0 1-2 0Zm6 0a1 1 0 1 0-2 0 1 1 0 0 0 2 0Zm2 0a1 1 0 1 1 2 0 1 1 0 0 1-2 0Z"
                  clip-rule="evenodd"
                />
              </svg>
            </div>
            <input
              id="datepicker-range-end"
              ref="endDateInput"
              name="end"
              type="text"
              class="w-full rounded-lg border border-gray-300 bg-gray-50 p-2 ps-9 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
              placeholder="End date"
            />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
