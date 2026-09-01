<script setup lang="ts">
import { provide, ref, onMounted, watch, nextTick } from 'vue'
// Component name is intentionally single-word for generic UI component

export interface Tab {
  id: string
  label: string
  target: string
  icon?: string // SVG path for icon
  iconViewBox?: string // SVG viewBox (default: '0 0 24 24')
}

interface Props {
  tabs: Tab[]
  defaultTab?: string
  contentId?: string
  activeClasses?: string
  inactiveClasses?: string
  storageKey?: string
}

const props = withDefaults(defineProps<Props>(), {
  defaultTab: '',
  contentId: 'tabs-content',
  activeClasses: 'text-primary-600 border-primary-600 dark:text-primary-500 dark:border-primary-500',
  inactiveClasses:
    'text-gray-500 border-transparent hover:text-gray-600 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300',
  storageKey: '',
})

const emit = defineEmits<{
  tabChange: [tabId: string]
}>()

function resolveInitialTab(): string {
  if (props.storageKey) {
    const isReload = (performance.getEntriesByType('navigation')[0] as PerformanceNavigationTiming)?.type === 'reload'
    if (isReload) {
      const stored = sessionStorage.getItem(props.storageKey)
      if (stored && props.tabs.some(t => t.id === stored)) {
        return stored
      }
    } else {
      sessionStorage.removeItem(props.storageKey)
    }
  }
  return props.defaultTab || props.tabs[0]?.id || ''
}

const activeTab = ref(resolveInitialTab())

// Provide active tab to child components
provide('activeTab', activeTab)

const setActiveTab = (tabId: string) => {
  activeTab.value = tabId
  if (props.storageKey) {
    sessionStorage.setItem(props.storageKey, tabId)
  }
  emit('tabChange', tabId)
}

// Programmatically activate tab
const activateTab = (tabId: string) => {
  const tabButton = document.getElementById(`${tabId}-tab`)
  if (tabButton) {
    tabButton.click()
  }
}

// Watch for changes in defaultTab prop
watch(() => props.defaultTab, (newTab) => {
  if (newTab && newTab !== activeTab.value) {
    activeTab.value = newTab
    nextTick(() => {
      activateTab(newTab)
    })
  }
}, { immediate: true })

onMounted(() => {
  if (typeof window !== 'undefined') {
    const windowWithFlowbite = window as Window & { initFlowbite?: () => void }
    if (windowWithFlowbite.initFlowbite) {
      setTimeout(() => {
        windowWithFlowbite.initFlowbite?.()
        
        const tabToActivate = activeTab.value
        if (tabToActivate) {
          setTimeout(() => {
            activateTab(tabToActivate)
          }, 50)
        }
      }, 100)
    }
  }
})
</script>

<!-- eslint-disable vue/multi-word-component-names -->
<template>
  <div>
    <!-- Tabs Navigation -->
    <div class="mb-6 border-b border-gray-200 dark:border-gray-700">
      <ul
        class="-mb-px flex flex-wrap text-center text-sm font-medium"
        :id="`${contentId}-tabs`"
        :data-tabs-toggle="`#${contentId}`"
        role="tablist"
        :data-tabs-active-classes="activeClasses"
        :data-tabs-inactive-classes="inactiveClasses"
      >
        <li v-for="(tab, index) in tabs" :key="tab.id" class="mr-2" role="presentation">
          <button
            class="inline-flex items-center justify-center rounded-t-lg border-b-2 p-4"
            :id="`${tab.id}-tab`"
            :data-tabs-target="`#${tab.target}`"
            type="button"
            role="tab"
            :aria-controls="tab.target"
            :aria-selected="index === 0"
            @click="setActiveTab(tab.id)"
          >
            <svg
              v-if="tab.icon"
              class="w-4 h-4 me-2"
              aria-hidden="true"
              xmlns="http://www.w3.org/2000/svg"
              fill="currentColor"
              :viewBox="tab.iconViewBox || '0 0 24 24'"
            >
              <path :d="tab.icon" />
            </svg>
            {{ tab.label }}
          </button>
        </li>
      </ul>
    </div>

    <!-- Tabs Content -->
    <div :id="contentId">
      <slot />
    </div>
  </div>
</template>

