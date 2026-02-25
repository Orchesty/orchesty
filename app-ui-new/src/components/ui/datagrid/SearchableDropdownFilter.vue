<script setup lang="ts">
import { computed, onMounted, onBeforeUnmount, ref, nextTick } from 'vue'

interface DropdownFilterOption {
  value: string | null
  label: string
}

interface Props {
  modelValue: string | null
  options: DropdownFilterOption[]
  buttonLabel?: string
  dropdownId?: string
  placeholder?: string
  searchPlaceholder?: string
  fullWidth?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  searchPlaceholder: 'Search...',
  fullWidth: false,
})

const emit = defineEmits<{
  'update:modelValue': [value: string | null]
}>()

const generatedId = ref(`searchable-dropdown-${Math.random().toString(36).substr(2, 9)}`)
const dropdownIdValue = computed(() => props.dropdownId || generatedId.value)

const searchQuery = ref('')

const displayLabel = computed(() => {
  if (props.buttonLabel) return props.buttonLabel
  const currentOption = props.options.find(opt => opt.value === props.modelValue)
  return currentOption?.label || props.placeholder || props.options[0]?.label || 'Select'
})

const filteredOptions = computed(() => {
  if (!searchQuery.value) return props.options
  const q = searchQuery.value.toLowerCase()
  return props.options.filter(opt => opt.label.toLowerCase().includes(q))
})

// eslint-disable-next-line @typescript-eslint/no-explicit-any
const dropdownInstanceRef = ref<any>(null)
const searchInputRef = ref<HTMLInputElement | null>(null)
let observer: MutationObserver | null = null

const handleSelect = async (value: string | null) => {
  emit('update:modelValue', value)
  searchQuery.value = ''
  await nextTick()
  if (dropdownInstanceRef.value) {
    dropdownInstanceRef.value.hide()
  }
}

onMounted(async () => {
  await nextTick()

  const dropdownElement = document.getElementById(dropdownIdValue.value)
  const buttonElement = document.getElementById(`${dropdownIdValue.value}-button`)

  if (dropdownElement && buttonElement) {
    const { Dropdown } = await import('flowbite')

    dropdownInstanceRef.value = new Dropdown(dropdownElement, buttonElement, {
      placement: 'bottom',
      triggerType: 'click',
      offsetSkidding: 0,
      offsetDistance: 10,
    })

    observer = new MutationObserver(() => {
      const isVisible = !dropdownElement.classList.contains('hidden')
      if (isVisible) {
        nextTick(() => searchInputRef.value?.focus())
      } else {
        searchQuery.value = ''
      }
    })
    observer.observe(dropdownElement, { attributes: true, attributeFilter: ['class'] })
  }
})

onBeforeUnmount(() => {
  observer?.disconnect()
})
</script>

<template>
  <div>
    <button
      :id="`${dropdownIdValue}-button`"
      :class="[
        'flex items-center justify-between rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 focus:outline-none dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white',
        props.fullWidth ? 'w-full' : 'min-w-40',
      ]"
      type="button"
    >
      <span class="text-left">{{ displayLabel }}</span>
      <svg
        class="ms-1.5 h-4 w-4 flex-shrink-0"
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
      class="z-50 hidden w-60 list-none divide-y divide-gray-100 rounded-lg bg-white text-sm font-medium shadow-sm dark:divide-gray-600 dark:bg-gray-700"
    >
      <!-- Search Input -->
      <div class="p-2">
        <div class="relative">
          <div class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3">
            <svg
              class="h-4 w-4 text-gray-500 dark:text-gray-400"
              aria-hidden="true"
              xmlns="http://www.w3.org/2000/svg"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"
              />
            </svg>
          </div>
          <input
            ref="searchInputRef"
            v-model="searchQuery"
            type="text"
            :placeholder="searchPlaceholder"
            class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2 ps-9 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-500 dark:bg-gray-600 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
          />
        </div>
      </div>

      <!-- Options List -->
      <ul class="max-h-64 overflow-y-auto p-2 text-gray-500 dark:text-gray-400" role="none">
        <li v-for="option in filteredOptions" :key="option.value || 'null'">
          <button
            type="button"
            class="inline-flex w-full rounded-md px-3 py-2 text-left hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
            role="menuitem"
            @click="handleSelect(option.value)"
          >
            {{ option.label }}
          </button>
        </li>
        <li v-if="filteredOptions.length === 0" class="px-3 py-2 text-gray-400 dark:text-gray-500">
          No results found
        </li>
      </ul>
    </div>
  </div>
</template>
