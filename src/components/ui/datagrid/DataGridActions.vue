<script setup lang="ts">
import type { ActionConfig } from '@/types/datagrid'

interface Props {
  actions: ActionConfig[]
  row: Record<string, any>
}

const props = defineProps<Props>()

// Filter actions based on show condition
const visibleActions = props.actions.filter((action) => {
  if (action.show === undefined) return true
  return action.show(props.row)
})

const handleClick = (action: ActionConfig) => {
  action.onClick(props.row)
}

// Icon SVG paths
const icons = {
  search: 'm21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z',
  edit: 'm14.304 4.844 2.852 2.852M7 7H4a1 1 0 0 0-1 1v10a1 1 0 0 0 1 1h11a1 1 0 0 0 1-1v-4.5m2.409-9.91a2.017 2.017 0 0 1 0 2.853l-6.844 6.844L8 14l.713-3.565 6.844-6.844a2.015 2.015 0 0 1 2.852 0Z',
  delete: 'M5 7h14m-9 3v8m4-8v8M10 3h4a1 1 0 0 1 1 1v3H9V4a1 1 0 0 1 1-1ZM6 7h12v13a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V7Z',
  download: 'M4 15v2a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3v-2m-8 1V4m0 12-4-4m4 4 4-4',
  more: 'M12 6h.01M12 12h.01M12 18h.01',
}
</script>

<template>
  <div class="flex items-center gap-1">
    <button
      v-for="(action, index) in visibleActions"
      :key="index"
      :title="action.title"
      @click="handleClick(action)"
      class="inline-flex items-center rounded-lg p-1 text-center text-sm font-medium text-gray-500 hover:bg-gray-200 hover:text-gray-900 focus:outline-hidden dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
    >
      <svg
        class="h-5 w-5"
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
          :d="icons[action.icon]"
        />
      </svg>
      <span class="sr-only">{{ action.title }}</span>
    </button>
  </div>
</template>

