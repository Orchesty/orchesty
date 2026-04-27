<script setup lang="ts">
import { onMounted, nextTick } from 'vue'
import { RouterLink } from 'vue-router'

export interface DropdownMenuItem {
  type: 'link' | 'button' | 'custom'
  label?: string
  icon?: string
  onClick?: () => void
  to?: string
  class?: string
  slotName?: string // for custom content
}

export interface DropdownMenuSection {
  items: DropdownMenuItem[]
  header?: {
    title?: string
    subtitle?: string
  }
}

interface Props {
  id: string
  sections: DropdownMenuSection[]
  width?: string
  placement?: 'bottom' | 'top' | 'left' | 'right'
  /**
   * When true, the trigger and its wrapper take full width of the parent
   * container (useful for select-style dropdowns). Default keeps the
   * historical `inline-flex` behaviour so existing call sites are untouched.
   */
  block?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  width: 'w-56',
  placement: 'bottom',
  block: false,
})

onMounted(async () => {
  await nextTick()
  
  const dropdownElement = document.getElementById(props.id)
  const buttonElement = document.getElementById(`${props.id}-button`)
  
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
  <div :class="['relative items-center', block ? 'flex w-full' : 'inline-flex']">
    <!-- Trigger Button -->
    <button
      :id="`${id}-button`"
      :data-dropdown-toggle="id"
      type="button"
      :class="['items-center', block ? 'flex w-full' : 'inline-flex']"
    >
      <slot name="trigger"></slot>
    </button>

    <!-- Dropdown Menu -->
    <div
      :id="id"
      :class="[
        'z-50 hidden list-none divide-y divide-gray-100 rounded-lg bg-white text-base shadow-sm dark:divide-gray-600 dark:bg-gray-700',
        width
      ]"
    >
      <!-- Sections -->
      <div
        v-for="(section, sectionIndex) in sections"
        :key="sectionIndex"
      >
        <!-- Section Header (optional) -->
        <div v-if="section.header" class="px-4 py-3">
          <span
            v-if="section.header.title"
            class="block text-sm font-semibold text-gray-900 dark:text-white"
          >
            {{ section.header.title }}
          </span>
          <span
            v-if="section.header.subtitle"
            class="block truncate text-sm text-gray-500 dark:text-gray-400"
          >
            {{ section.header.subtitle }}
          </span>
        </div>

        <!-- Section Items -->
        <ul
          class="text-gray-500 dark:text-gray-400"
          :class="{ 'py-1': !section.header, 'py-2': section.header }"
        >
          <li v-for="(item, itemIndex) in section.items" :key="itemIndex">
            <!-- External Link Item -->
            <a
              v-if="item.type === 'link' && item.to && (item.to.startsWith('http://') || item.to.startsWith('https://'))"
              :href="item.to"
              :class="[
                'block px-4 py-2 text-sm hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white',
                item.class
              ]"
            >
              <span v-if="item.icon" v-html="item.icon" class="mr-2 inline-block h-4 w-4"></span>
              {{ item.label }}
            </a>

            <!-- Internal Link Item -->
            <RouterLink
              v-else-if="item.type === 'link' && item.to"
              :to="item.to"
              :class="[
                'block px-4 py-2 text-sm hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white',
                item.class
              ]"
            >
              <span v-if="item.icon" v-html="item.icon" class="mr-2 inline-block h-4 w-4"></span>
              {{ item.label }}
            </RouterLink>

            <!-- Button Item -->
            <button
              v-else-if="item.type === 'button'"
              type="button"
              :class="[
                'block w-full px-4 py-2 text-left text-sm hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white',
                item.class
              ]"
              @click="item.onClick"
            >
              <span v-if="item.icon" v-html="item.icon" class="mr-2 inline-block h-4 w-4"></span>
              {{ item.label }}
            </button>

            <!-- Custom Slot Item -->
            <div v-else-if="item.type === 'custom' && item.slotName">
              <slot :name="item.slotName"></slot>
            </div>
          </li>
        </ul>
      </div>
    </div>
  </div>
</template>

