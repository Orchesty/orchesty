<!-- eslint-disable vue/multi-word-component-names -->
<script setup lang="ts">
import { ref } from 'vue'

interface Props {
  value: string
  hideValue?: boolean
  title?: string
}

const props = withDefaults(defineProps<Props>(), {
  hideValue: false,
  title: 'Copy to clipboard',
})

const copied = ref(false)

const copyToClipboard = async () => {
  try {
    await navigator.clipboard.writeText(props.value)
    copied.value = true
    setTimeout(() => {
      copied.value = false
    }, 2000)
  } catch (err) {
    console.error('Failed to copy:', err)
  }
}
</script>

<template>
  <span v-if="!hideValue" class="inline-flex items-center gap-2">
    <slot>{{ value }}</slot>
    <button
      type="button"
      :title="copied ? 'Copied!' : title"
      class="inline-flex items-center rounded-lg p-1 text-center text-sm font-medium transition-colors focus:outline-none"
      :class="
        copied
          ? 'text-green-600 dark:text-green-400'
          : 'text-gray-500 hover:bg-gray-200 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white'
      "
      @click="copyToClipboard"
    >
      <svg
        v-if="!copied"
        class="h-4 w-4"
        aria-hidden="true"
        xmlns="http://www.w3.org/2000/svg"
        height="24px"
        viewBox="0 -960 960 960"
        width="24px"
        fill="currentColor"
      >
        <path
          d="M360-240q-33 0-56.5-23.5T280-320v-480q0-33 23.5-56.5T360-880h360q33 0 56.5 23.5T800-800v480q0 33-23.5 56.5T720-240H360Zm0-80h360v-480H360v480ZM200-80q-33 0-56.5-23.5T120-160v-560h80v560h440v80H200Zm160-240v-480 480Z"
        />
      </svg>
      <svg
        v-else
        class="h-4 w-4"
        aria-hidden="true"
        xmlns="http://www.w3.org/2000/svg"
        height="24px"
        viewBox="0 -960 960 960"
        width="24px"
        fill="currentColor"
      >
        <path d="M382-240 154-468l57-57 171 171 367-367 57 57-424 424Z" />
      </svg>
      <span class="sr-only">{{ copied ? 'Copied!' : title }}</span>
    </button>
  </span>
  <button
    v-else
    type="button"
    :title="copied ? 'Copied!' : title"
    class="inline-flex items-center rounded-lg p-1 text-center text-sm font-medium transition-colors focus:outline-none"
    :class="
      copied
        ? 'text-green-600 dark:text-green-400'
        : 'text-gray-500 hover:bg-gray-200 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white'
    "
    @click="copyToClipboard"
  >
    <svg
      v-if="!copied"
      class="h-5 w-5"
      aria-hidden="true"
      xmlns="http://www.w3.org/2000/svg"
      height="24px"
      viewBox="0 -960 960 960"
      width="24px"
      fill="currentColor"
    >
      <path
        d="M360-240q-33 0-56.5-23.5T280-320v-480q0-33 23.5-56.5T360-880h360q33 0 56.5 23.5T800-800v480q0 33-23.5 56.5T720-240H360Zm0-80h360v-480H360v480ZM200-80q-33 0-56.5-23.5T120-160v-560h80v560h440v80H200Zm160-240v-480 480Z"
      />
    </svg>
    <svg
      v-else
      class="h-5 w-5"
      aria-hidden="true"
      xmlns="http://www.w3.org/2000/svg"
      height="24px"
      viewBox="0 -960 960 960"
      width="24px"
      fill="currentColor"
    >
      <path d="M382-240 154-468l57-57 171 171 367-367 57 57-424 424Z" />
    </svg>
    <span class="sr-only">{{ copied ? 'Copied!' : title }}</span>
  </button>
</template>

