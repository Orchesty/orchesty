<script setup lang="ts">
/**
 * MoreActions - Ellipsis dropdown menu for contextual actions
 * 
 * A borderless icon-only button with a dropdown menu.
 */
import { onMounted, nextTick, computed } from 'vue'
import { Ellipsis } from 'lucide-vue-next'
import { RouterLink } from 'vue-router'

export interface MoreActionsItem {
  type: 'link' | 'button'
  label: string
  icon?: string
  onClick?: () => void
  to?: string
  class?: string
}

export interface MoreActionsSection {
  items: MoreActionsItem[]
}

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
  width: 'w-44',
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
      offsetDistance: 10,
    })
  }
})
</script>

<template>
  <div class="relative inline-flex">
    <button
      :id="buttonId"
      type="button"
      class="inline-flex items-center justify-center h-10 w-10 rounded-full text-gray-500 hover:bg-gray-100 hover:text-gray-900 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
    >
      <Ellipsis class="h-5 w-5" aria-hidden="true" />
      <span class="sr-only">More actions</span>
    </button>

    <div
      :id="id"
      :class="[
        'z-50 hidden divide-y divide-gray-100 rounded-lg bg-white shadow dark:divide-gray-600 dark:bg-gray-700',
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
