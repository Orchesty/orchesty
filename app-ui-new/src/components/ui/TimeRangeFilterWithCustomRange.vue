<script setup lang="ts">
import { ref, computed, onMounted, nextTick } from 'vue'

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
  ],
})

const emit = defineEmits<{
  'update:modelValue': [value: string]
}>()

// Generate unique ID for dropdown
const dropdownId = ref(`time-range-filter-${Math.random().toString(36).substr(2, 9)}`)

// eslint-disable-next-line @typescript-eslint/no-explicit-any
const dropdownInstance = ref<any>(null)

// Get current label
const currentLabel = computed(() => {
  const option = props.options.find((opt) => opt.value === props.modelValue)
  return option?.label || 'Select period'
})

const handleSelect = (value: string) => {
  emit('update:modelValue', value)
  
  // Close dropdown
  if (dropdownInstance.value) {
    dropdownInstance.value.hide()
  }
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
    })
  }
})
</script>

<template>
  <div>
    <button
      :id="`${dropdownId}-button`"
      :data-dropdown-toggle="dropdownId"
      class="flex items-center justify-center whitespace-nowrap rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 focus:z-10 focus:outline-none dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
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
      class="z-50 my-4 hidden w-80 list-none rounded-lg bg-white text-sm font-medium shadow-sm dark:bg-gray-700"
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
    </div>
  </div>
</template>

