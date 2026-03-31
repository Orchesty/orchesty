<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, nextTick, watch } from 'vue';
import DropdownMenu from '@/components/ui/DropdownMenu.vue';

export interface TabDefinition {
  id: string;
  label: string;
  disabled?: boolean;
}

interface Props {
  tabs: TabDefinition[];
  activeTab?: string;
}

const props = withDefaults(defineProps<Props>(), {
  activeTab: undefined,
});

const emit = defineEmits<{
  'tab-change': [tabId: string];
}>();

const tabsContainerRef = ref<HTMLElement | null>(null);
const tabsListRef = ref<HTMLElement | null>(null);
const tabRefs = ref<HTMLElement[]>([]);
const moreButtonRef = ref<HTMLElement | null>(null);

const visibleTabs = ref<TabDefinition[]>([]);
const hiddenTabs = ref<TabDefinition[]>([]);
const hasOverflow = ref(false);

let resizeObserver: ResizeObserver | null = null;

const currentActiveTab = ref(
  props.activeTab || (props.tabs.length > 0 ? props.tabs[0]?.id ?? '' : ''),
);

const moreDropdownItems = computed(() => {
  return hiddenTabs.value
    .filter(tab => !tab.disabled)
    .map(tab => ({
      type: 'button' as const,
      label: tab.label,
      onClick: () => handleTabClick(tab.id),
    }));
});

const calculateTabsLayout = () => {
  if (!tabsListRef.value || !tabsContainerRef.value) return;

  const containerWidth = tabsContainerRef.value.clientWidth;
  const moreButtonWidth = 100; // Approximate width of "More (...)" button
  const availableWidth = containerWidth - moreButtonWidth - 20; // 20px buffer

  let currentWidth = 0;
  const visible: TabDefinition[] = [];
  const hidden: TabDefinition[] = [];

  props.tabs.forEach((tab, index) => {
    const tabElement = tabRefs.value[index];
    if (!tabElement) return;

    const tabWidth = tabElement.offsetWidth + 8; // 8px for mr-2 margin

    if (currentWidth + tabWidth <= availableWidth || visible.length === 0) {
      visible.push(tab);
      currentWidth += tabWidth;
    } else {
      hidden.push(tab);
    }
  });

  // If all tabs fit, don't show the More button
  if (hidden.length === 0) {
    visibleTabs.value = props.tabs;
    hiddenTabs.value = [];
    hasOverflow.value = false;
  } else {
    visibleTabs.value = visible;
    hiddenTabs.value = hidden;
    hasOverflow.value = true;
  }
};

const isTabDisabled = (tabId: string): boolean => {
  return props.tabs.find(t => t.id === tabId)?.disabled ?? false;
};

const handleTabClick = (tabId: string) => {
  if (isTabDisabled(tabId)) return;
  currentActiveTab.value = tabId;
  emit('tab-change', tabId);
};

const getTabButtonClasses = (tabId: string) => {
  const baseClasses = 'inline-flex items-center justify-center p-4 border-b-2 rounded-t-lg whitespace-nowrap';
  const activeClasses = 'text-primary-600 border-primary-600 dark:text-primary-500 dark:border-primary-500';
  const inactiveClasses = 'text-gray-500 border-transparent hover:text-gray-600 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300';
  const disabledClasses = 'text-gray-300 border-transparent cursor-not-allowed dark:text-gray-600';

  if (isTabDisabled(tabId)) return `${baseClasses} ${disabledClasses}`;
  return `${baseClasses} ${currentActiveTab.value === tabId ? activeClasses : inactiveClasses}`;
};

onMounted(async () => {
  await nextTick();
  
  // Ensure we have an active tab set
  if (!currentActiveTab.value && props.tabs.length > 0) {
    currentActiveTab.value = props.tabs[0]?.id ?? '';
  }
  
  // Initial layout calculation
  calculateTabsLayout();

  // Set up ResizeObserver to recalculate on container resize
  if (tabsContainerRef.value) {
    resizeObserver = new ResizeObserver(() => {
      calculateTabsLayout();
    });
    resizeObserver.observe(tabsContainerRef.value);
  }
});

// Watch for activeTab prop changes (including empty string to deselect all)
watch(() => props.activeTab, (newTab) => {
  if (newTab !== undefined && newTab !== currentActiveTab.value) {
    currentActiveTab.value = newTab;
  }
});

onUnmounted(() => {
  if (resizeObserver) {
    resizeObserver.disconnect();
  }
});
</script>

<template>
  <div ref="tabsContainerRef" class="border-b border-gray-200 dark:border-gray-700">
    <ul
      ref="tabsListRef"
      class="flex flex-nowrap -mb-px text-sm font-medium"
      role="tablist"
    >
      <!-- Visible tabs -->
      <li
        v-for="(tab, index) in visibleTabs"
        :key="tab.id"
        :ref="el => { if (el) { const idx = props.tabs.findIndex(t => t.id === tab.id); if (idx >= 0) tabRefs[idx] = el as HTMLElement; } }"
        class="mr-2"
        role="presentation"
      >
        <button
          :id="`${tab.id}-tab-btn`"
          :class="getTabButtonClasses(tab.id)"
          type="button"
          role="tab"
          :aria-controls="`${tab.id}-tab-content`"
          :aria-selected="currentActiveTab === tab.id"
          :disabled="isTabDisabled(tab.id)"
          :aria-disabled="isTabDisabled(tab.id)"
          @click="handleTabClick(tab.id)"
        >
          {{ tab.label }}
        </button>
      </li>

      <!-- Hidden tabs measurement refs (absolute positioned off-screen) -->
      <template v-for="(tab, index) in tabs" :key="`measure-${tab.id}`">
        <li
          v-if="!visibleTabs.find(t => t.id === tab.id)"
          :ref="el => { if (el && index < tabs.length) tabRefs[index] = el as HTMLElement }"
          class="mr-2 invisible absolute"
          style="left: -9999px;"
          role="presentation"
          aria-hidden="true"
        >
          <button
            class="inline-flex items-center justify-center p-4 border-b-2 rounded-t-lg whitespace-nowrap"
            type="button"
            tabindex="-1"
          >
            {{ tab.label }}
          </button>
        </li>
      </template>

      <!-- More dropdown button -->
      <li v-if="hasOverflow" ref="moreButtonRef" class="mr-2" role="presentation">
        <DropdownMenu
          id="tabs-overflow-more-menu"
          :sections="[{ items: moreDropdownItems }]"
          placement="bottom"
        >
          <template #trigger>
            <span
              class="inline-flex items-center justify-center p-4 border-b-2 rounded-t-lg text-gray-500 border-transparent hover:text-gray-600 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300"
            >
              More (...)
            </span>
          </template>
        </DropdownMenu>
      </li>

      <!-- Right-aligned action slot -->
      <li v-if="$slots['right-action']" class="ml-auto flex items-center" role="presentation">
        <slot name="right-action" />
      </li>
    </ul>
  </div>

  <!-- Tab content panels -->
  <div>
    <div
      v-for="tab in tabs"
      :id="`${tab.id}-tab-content`"
      :key="`content-${tab.id}`"
      role="tabpanel"
      :aria-labelledby="`${tab.id}-tab-btn`"
      class="py-6"
      :style="{ display: currentActiveTab === tab.id ? 'block' : 'none' }"
    >
      <slot :name="`tab-content-${tab.id}`" :tab="tab" />
    </div>
  </div>
</template>

