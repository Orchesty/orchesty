<script setup lang="ts">
import { ref, computed } from 'vue'
import { TopologyDetailView, TabCard, Button } from '@orchesty/ui-core'
import type { TopologyTab } from '@orchesty/ui-core'
import { useFeatures } from '@/composables/useFeatures'

interface Props {
  id: string
}

defineProps<Props>()

const { pulse } = useFeatures()

const hiddenTabs = computed(() => {
  const tabs: string[] = []
  if (!pulse.value) tabs.push('context')
  return tabs
})

const extraTabs: TopologyTab[] = [
  { id: 'access', label: 'Access' },
]

interface AccessGroup {
  id: string
  name: string
  permission: 'manager' | 'developer' | 'user'
}

const accessGroups = ref<AccessGroup[]>([
  { id: 'group-1', name: 'Administrators', permission: 'manager' },
  { id: 'group-2', name: 'Developers', permission: 'developer' },
])

const availableGroups = computed(() => [
  'Administrators',
  'Developers',
  'Operators',
  'Support Team',
  'QA Team',
])

const handleAddGroup = (groupName: string) => {
  const newGroup: AccessGroup = {
    id: `group-${Date.now()}`,
    name: groupName,
    permission: 'user',
  }
  accessGroups.value.push(newGroup)
}

const handleRemoveGroup = (groupId: string) => {
  accessGroups.value = accessGroups.value.filter((g) => g.id !== groupId)
}

const handlePermissionChange = (groupId: string, permission: 'manager' | 'developer' | 'user') => {
  const group = accessGroups.value.find((g) => g.id === groupId)
  if (group) {
    group.permission = permission
  }
}
</script>

<template>
  <TopologyDetailView :id="id" :extra-tabs="extraTabs" :hidden-tabs="hiddenTabs">
    <template #extra-tab-content="{ activeTab }">
      <div v-show="activeTab === 'access'">
        <TabCard>
          <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Access Control</h3>
            <Button data-dropdown-toggle="add-group-dropdown">
              <svg class="h-4 w-4 me-2" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
              </svg>
              Group
            </Button>
            <div id="add-group-dropdown" class="z-10 hidden bg-white divide-y divide-gray-100 rounded-lg shadow-sm w-60 dark:bg-gray-700">
              <ul class="py-2 text-sm text-gray-700 dark:text-gray-200">
                <li v-for="groupName in availableGroups" :key="groupName">
                  <button
                    type="button"
                    @click="handleAddGroup(groupName)"
                    class="block w-full px-4 py-2 text-left hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white"
                  >
                    {{ groupName }}
                  </button>
                </li>
              </ul>
            </div>
          </div>

          <div class="space-y-4">
            <div
              v-for="group in accessGroups"
              :key="group.id"
              class="rounded-lg border border-gray-200 bg-white p-6 shadow-xs dark:border-gray-700 dark:bg-gray-800"
            >
              <div class="mb-4 flex items-center justify-between">
                <h4 class="text-base font-semibold text-gray-900 dark:text-white">{{ group.name }}</h4>
                <Button variant="outline" size="sm" @click="handleRemoveGroup(group.id)">
                  <svg class="h-4 w-4 me-2" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7H5m14 0-1 12a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 7m14 0H5m3 0V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m-5 5v6m4-6v6" />
                  </svg>
                  Remove
                </Button>
              </div>
              <div class="space-y-3">
                <div v-for="perm in (['manager', 'developer', 'user'] as const)" :key="perm" class="flex items-start">
                  <div class="flex h-5 items-center">
                    <input
                      :id="`${group.id}-${perm}`"
                      :name="`${group.id}-permission`"
                      type="radio"
                      :value="perm"
                      :checked="group.permission === perm"
                      @change="handlePermissionChange(group.id, perm)"
                      class="h-4 w-4 border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
                    >
                  </div>
                  <div class="ms-3 text-sm">
                    <label :for="`${group.id}-${perm}`" class="font-medium text-gray-900 dark:text-white">{{ perm.charAt(0).toUpperCase() + perm.slice(1) }}</label>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                      {{ perm === 'manager' ? 'Full access including managing permissions, deleting topology, and all development features'
                       : perm === 'developer' ? 'Can edit topology configuration, manage nodes, and run processes'
                       : 'View-only access with ability to run topology but cannot edit configuration' }}
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </TabCard>
      </div>
    </template>
  </TopologyDetailView>
</template>
