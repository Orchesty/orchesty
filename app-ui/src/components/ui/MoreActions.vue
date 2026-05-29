<script setup lang="ts">
/**
 * MoreActions - Ellipsis dropdown menu for contextual actions
 * 
 * Uses Teleport to body to avoid overflow clipping in grids/tables.
 */
import { ref, onMounted, onBeforeUnmount, nextTick } from 'vue'
import { Ellipsis } from 'lucide-vue-next'
import { RouterLink } from 'vue-router'

export interface MoreActionsItem {
  type: 'link' | 'button'
  label: string
  icon?: string
  onClick?: () => void
  to?: string
  class?: string
  loading?: boolean
  disabled?: boolean
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

withDefaults(defineProps<Props>(), {
  width: 'w-auto min-w-[11rem]',
  placement: 'bottom-end',
})

const isOpen = ref(false)
const buttonRef = ref<HTMLElement | null>(null)
const menuRef = ref<HTMLElement | null>(null)
const menuStyle = ref<Record<string, string>>({})

function updatePosition() {
  if (!buttonRef.value) return
  const rect = buttonRef.value.getBoundingClientRect()

  menuStyle.value = {
    position: 'fixed',
    top: `${rect.bottom + 4}px`,
    right: `${window.innerWidth - rect.right}px`,
    zIndex: '9999',
  }
}

async function toggle() {
  isOpen.value = !isOpen.value
  if (isOpen.value) {
    updatePosition()
    await nextTick()
    updatePosition()
  }
}

function close() {
  isOpen.value = false
}

function handleItemClick(item: MoreActionsItem) {
  if (item.loading || item.disabled) return
  if (item.onClick) {
    item.onClick()
  }
  close()
}

function onClickOutside(event: MouseEvent) {
  const target = event.target as Node
  if (
    buttonRef.value && !buttonRef.value.contains(target) &&
    menuRef.value && !menuRef.value.contains(target)
  ) {
    close()
  }
}

function onScroll() {
  if (isOpen.value) {
    updatePosition()
  }
}

onMounted(() => {
  document.addEventListener('click', onClickOutside, true)
  window.addEventListener('scroll', onScroll, true)
})

onBeforeUnmount(() => {
  document.removeEventListener('click', onClickOutside, true)
  window.removeEventListener('scroll', onScroll, true)
})
</script>

<template>
  <div class="relative inline-flex">
    <button
      ref="buttonRef"
      type="button"
      class="inline-flex items-center justify-center h-10 w-10 rounded-full text-gray-500 hover:bg-gray-100 hover:text-gray-900 focus:outline-hidden dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
      @click.stop="toggle"
    >
      <Ellipsis class="h-5 w-5" aria-hidden="true" />
      <span class="sr-only">More actions</span>
    </button>

    <Teleport to="body">
      <Transition
        enter-active-class="transition ease-out duration-100"
        enter-from-class="transform opacity-0 scale-95"
        enter-to-class="transform opacity-100 scale-100"
        leave-active-class="transition ease-in duration-75"
        leave-from-class="transform opacity-100 scale-100"
        leave-to-class="transform opacity-0 scale-95"
      >
        <div
          v-if="isOpen"
          ref="menuRef"
          :style="menuStyle"
          :class="[
            'whitespace-nowrap divide-y divide-gray-100 rounded-lg bg-white shadow-lg ring-1 ring-black/5 dark:divide-gray-600 dark:bg-gray-700',
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
                  @click="close"
                >
                  <span v-if="item.icon" v-html="item.icon" class="mr-2 inline-block h-4 w-4" />
                  {{ item.label }}
                </RouterLink>

                <button
                  v-else-if="item.type === 'button'"
                  type="button"
                  :disabled="item.loading || item.disabled"
                  :class="[
                    'flex w-full items-center px-4 py-2 text-left text-sm hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white',
                    (item.loading || item.disabled) && 'cursor-not-allowed opacity-60 hover:bg-transparent dark:hover:bg-transparent',
                    item.class
                  ]"
                  @click="handleItemClick(item)"
                >
                  <svg
                    v-if="item.loading"
                    class="mr-2 h-4 w-4 animate-spin"
                    aria-hidden="true"
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                  >
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                  <span v-else-if="item.icon" v-html="item.icon" class="mr-2 inline-block h-4 w-4" />
                  {{ item.label }}
                </button>
              </li>
            </ul>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>
