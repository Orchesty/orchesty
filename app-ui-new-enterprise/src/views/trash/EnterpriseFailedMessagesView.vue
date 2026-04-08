<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import FailedMessagesByTopologyTab from '@/components/trash/FailedMessagesByTopologyTab.vue'
import FailedMessagesView from '@/views/trash/FailedMessagesView.vue'
import type { TrashTableRow } from '@/types/dashboard'

const router = useRouter()

const TAB_KEY = 'orchesty_failed_messages_tab'

type TabId = 'by-topology' | 'all-messages'

const tabs = [
  { id: 'by-topology' as const, label: 'By Topology' },
  { id: 'all-messages' as const, label: 'All Messages' },
]

const savedTab = localStorage.getItem(TAB_KEY) as TabId | null
const activeTab = ref<TabId>(savedTab && tabs.some(t => t.id === savedTab) ? savedTab : 'by-topology')

const handleTabClick = (tabId: TabId) => {
  activeTab.value = tabId
  localStorage.setItem(TAB_KEY, tabId)
}

function handleViewMessages(row: TrashTableRow) {
  handleTabClick('all-messages')
  const query: Record<string, string> = {}
  if (row.nodeId) query.node = row.nodeId
  if (row.topologyId) query.topologyId = row.topologyId
  if (row.message) query.search = row.message
  router.replace({ name: 'trash', query })
}

const activeTabClass = 'text-primary-600 border-primary-600 dark:text-primary-500 dark:border-primary-500'
const inactiveTabClass = 'text-gray-500 border-transparent hover:text-gray-600 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'
</script>

<template>
  <main class="h-full overflow-y-auto">
    <div class="px-4 pb-4 pt-6">
      <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Failed Messages</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
          View and manage failed messages from all topologies
        </p>
      </div>

      <div class="mb-6 border-b border-gray-200 dark:border-gray-700">
        <ul class="-mb-px flex flex-wrap text-center text-sm font-medium" role="tablist">
          <li v-for="tab in tabs" :key="tab.id" class="mr-2" role="presentation">
            <button
              class="inline-block rounded-t-lg border-b-2 p-4"
              :class="activeTab === tab.id ? activeTabClass : inactiveTabClass"
              type="button"
              role="tab"
              :aria-selected="activeTab === tab.id"
              @click="handleTabClick(tab.id)"
            >
              {{ tab.label }}
            </button>
          </li>
        </ul>
      </div>

      <KeepAlive>
        <FailedMessagesByTopologyTab
          v-if="activeTab === 'by-topology'"
          @view-messages="handleViewMessages"
        />
      </KeepAlive>
    </div>

    <KeepAlive>
      <div v-if="activeTab === 'all-messages'" class="all-messages-tab">
        <FailedMessagesView />
      </div>
    </KeepAlive>
  </main>
</template>

<style scoped>
.all-messages-tab :deep(> main) {
  overflow: visible !important;
}
.all-messages-tab :deep(> main > div > div:first-child) {
  display: none;
}
.all-messages-tab :deep(> main > div) {
  padding-top: 0;
}
</style>
