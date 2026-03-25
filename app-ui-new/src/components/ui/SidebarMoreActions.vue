<script setup lang="ts">
/**
 * SidebarMoreActions - Compact ellipsis dropdown for sidebar items
 *
 * A smaller variant of MoreActions designed for sidebar tree items.
 * Uses a compact p-1 button with a small horizontal dots icon.
 */
import { onMounted, nextTick, computed } from 'vue'
import { RouterLink } from 'vue-router'
import type { MoreActionsSection } from '@/components/ui/MoreActions.vue'

interface Props {
  /** Unique identifier for the dropdown */
  id: string
  /** Menu sections with items */
  sections: MoreActionsSection[]
  /** Dropdown width class */
  width?: string
  /** Dropdown placement */
  placement?: 'bottom' | 'bottom-end' | 'bottom-start' | 'top'
}

const props = withDefaults(defineProps<Props>(), {
  width: 'w-40',
  placement: 'bottom-end',
})

const buttonId = computed(() => `${props.id}-button`)

onMounted(async () => {
  await nextTick()

  const dropdownElement = document.getElementById(props.id)
  const buttonElement = document.getElementById(buttonId.value)

  if (dropdownElement && buttonElement) {
    const { Dropdown } = await import('flowbite')
    new Dropdown(dropdownElement, buttonElement, {
      placement: props.placement,
      triggerType: 'click',
      offsetSkidding: 0,
      offsetDistance: 4,
    })
  }
})
</script>

<template>
  <div class="relative inline-flex">
    <button
      :id="buttonId"
      type="button"
      class="inline-flex items-center rounded-lg p-1 text-center text-sm font-medium text-gray-500 hover:bg-gray-200 hover:text-gray-900 focus:outline-hidden dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
    >
      <svg
        class="w-4 h-4"
        aria-hidden="true"
        xmlns="http://www.w3.org/2000/svg"
        fill="none"
        viewBox="0 0 24 24"
      >
        <path
          stroke="currentColor"
          stroke-linecap="round"
          stroke-width="2"
          d="M6 12h.01m6 0h.01m5.99 0h.01"
        />
      </svg>
      <span class="sr-only">Actions</span>
    </button>

    <div
      :id="id"
      :class="[
        'z-50 hidden divide-y divide-gray-100 rounded-lg bg-white shadow-sm dark:divide-gray-600 dark:bg-gray-700',
        width
      ]"
    >
      <div v-for="(section, sectionIndex) in sections" :key="sectionIndex">
        <ul class="py-1 text-gray-500 dark:text-gray-400">
          <li v-for="(item, itemIndex) in section.items" :key="itemIndex">
            <RouterLink
              v-if="item.type === 'link' && item.to"
              :to="item.to"
              :class="[
                'block px-4 py-2 text-sm hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white',
                item.class
              ]"
            >
              <span v-if="item.icon" v-html="item.icon" class="mr-2 inline-block h-4 w-4" />
              {{ item.label }}
            </RouterLink>

            <button
              v-else-if="item.type === 'button'"
              type="button"
              :class="[
                'block w-full px-4 py-2 text-left text-sm hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white',
                item.class
              ]"
              @click="item.onClick"
            >
              <span v-if="item.icon" v-html="item.icon" class="mr-2 inline-block h-4 w-4" />
              {{ item.label }}
            </button>
          </li>
        </ul>
      </div>
    </div>
  </div>
</template>
