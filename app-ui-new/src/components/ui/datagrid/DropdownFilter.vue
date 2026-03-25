<script setup lang="ts">
import { computed, onMounted, ref, nextTick } from 'vue'

interface DropdownFilterOption {
  value: string | null
  label: string
}

interface Props {
  modelValue: string | null
  options: DropdownFilterOption[]
  buttonLabel?: string
  dropdownId?: string
  fullWidth?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  fullWidth: false
})

const emit = defineEmits<{
  'update:modelValue': [value: string | null]
}>()

// Generate unique ID if not provided
const generatedId = ref(`dropdown-filter-${Math.random().toString(36).substr(2, 9)}`)
const dropdownIdValue = computed(() => props.dropdownId || generatedId.value)

// Computed button label - use prop or find current option label
const displayLabel = computed(() => {
  if (props.buttonLabel) return props.buttonLabel
  
  const currentOption = props.options.find(opt => opt.value === props.modelValue)
  return currentOption?.label || props.options[0]?.label || 'Select'
})

// eslint-disable-next-line @typescript-eslint/no-explicit-any
const dropdownInstanceRef = ref<any>(null)

const handleSelect = async (value: string | null) => {
  // First emit the value change
  emit('update:modelValue', value)
  
  // Wait for next tick to ensure Vue updates
  await nextTick()
  
  // Then close dropdown using Flowbite instance
  if (dropdownInstanceRef.value) {
    dropdownInstanceRef.value.hide()
  } else {
    console.warn('Dropdown instance is null')
  }
}

onMounted(async () => {
  await nextTick()
  
  const dropdownElement = document.getElementById(dropdownIdValue.value)
  const buttonElement = document.getElementById(`${dropdownIdValue.value}-button`)
  
  if (dropdownElement && buttonElement) {
    // Import Dropdown class from flowbite
    const { Dropdown } = await import('flowbite')
    
    // Create our own Dropdown instance
    dropdownInstanceRef.value = new Dropdown(dropdownElement, buttonElement, {
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
      :id="`${dropdownIdValue}-button`"
      :class="[
        'flex items-center justify-between rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 focus:outline-hidden dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white',
        props.fullWidth ? 'w-full' : 'min-w-40'
      ]"
      type="button"
    >
      <span class="text-left">{{ displayLabel }}</span>
      <svg
        class="ms-1.5 h-4 w-4 shrink-0"
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
      :id="dropdownIdValue"
      class="z-50 hidden w-48 list-none divide-y divide-gray-100 rounded-lg bg-white text-sm font-medium shadow-xs dark:divide-gray-600 dark:bg-gray-700"
    >
      <ul class="max-h-64 overflow-y-auto p-2 text-gray-500 dark:text-gray-400" role="none">
        <li v-for="option in options" :key="option.value || 'null'">
          <button
            type="button"
            @click="handleSelect(option.value)"
            class="inline-flex w-full rounded-md px-3 py-2 text-left hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
            role="menuitem"
          >
            {{ option.label }}
          </button>
        </li>
      </ul>
    </div>
  </div>
</template>

