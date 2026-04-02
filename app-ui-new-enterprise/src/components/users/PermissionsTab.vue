<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import Card from '@/components/ui/Card.vue'
import { fetchGroups } from '@/services/groupsService'
import type { Group } from '@/types/users'
import { useToast } from '@/composables/useToast'

const { showToast } = useToast()

const groups = ref<Group[]>([])
const selectedGroupId = ref<string | null>(null)
const loading = ref(false)

const selectedGroup = computed(() =>
  groups.value.find((g) => g.id === selectedGroupId.value) ?? null,
)

interface PermissionItem {
  id: string
  label: string
  resource: string
  action: string
}

interface SectionDefinition {
  id: string
  label: string
  sectionResource: string
  permissions: PermissionItem[]
}

const sections: SectionDefinition[] = [
  {
    id: 'dashboard',
    label: 'Dashboard',
    sectionResource: 'section_dashboard',
    permissions: [],
  },
  {
    id: 'topologies',
    label: 'Integrations (Topologies)',
    sectionResource: 'section_topologies',
    permissions: [
      { id: 'topology-read', label: 'View topologies', resource: 'topology', action: 'read' },
      { id: 'topology-write', label: 'Manage topologies', resource: 'topology', action: 'write' },
      { id: 'topology-delete', label: 'Delete topologies', resource: 'topology', action: 'delete' },
      { id: 'node-read', label: 'View nodes', resource: 'node', action: 'read' },
      { id: 'node-write', label: 'Manage nodes', resource: 'node', action: 'write' },
    ],
  },
  {
    id: 'ai-assistant',
    label: 'AI Assistant',
    sectionResource: 'section_ai_assistant',
    permissions: [],
  },
  {
    id: 'analytics',
    label: 'Analytics',
    sectionResource: 'section_analytics',
    permissions: [],
  },
  {
    id: 'users',
    label: 'Users & Groups',
    sectionResource: 'section_users',
    permissions: [
      { id: 'user-read', label: 'View users', resource: 'user', action: 'read' },
      { id: 'user-write', label: 'Manage users', resource: 'user', action: 'write' },
      { id: 'user-delete', label: 'Delete users', resource: 'user', action: 'delete' },
      { id: 'group-read', label: 'View groups', resource: 'group', action: 'read' },
      { id: 'group-write', label: 'Manage groups', resource: 'group', action: 'write' },
    ],
  },
  {
    id: 'settings',
    label: 'Settings',
    sectionResource: 'section_settings',
    permissions: [],
  },
]

const expandedSections = ref<Set<string>>(new Set())

function toggleSection(sectionId: string) {
  if (expandedSections.value.has(sectionId)) {
    expandedSections.value.delete(sectionId)
  } else {
    expandedSections.value.add(sectionId)
  }
}

function hasPermission(resource: string, action: string): boolean {
  if (!selectedGroup.value) return false
  const rule = selectedGroup.value.rules.find((r) => r.resource === resource)
  if (!rule) return false
  return rule.actions.includes(action)
}

function handlePermissionChange() {
  showToast('Permissions management will be available in a future update.', 'info')
}

async function loadGroups() {
  loading.value = true
  try {
    const response = await fetchGroups()
    groups.value = response.items
    if (response.items.length > 0 && !selectedGroupId.value) {
      selectedGroupId.value = response.items[0].id
    }
  } catch (error) {
    console.error('Failed to load groups:', error)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadGroups()
})
</script>

<template>
  <Card>
    <div class="mb-6">
      <div class="flex items-center justify-between mb-2">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Permissions</h3>
      </div>
      <p class="text-sm text-gray-500 dark:text-gray-400">
        View and manage section access and resource permissions for each group.
      </p>
    </div>

    <!-- Group Selector -->
    <div class="mb-6">
      <label for="group-select" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
        Select group
      </label>
      <select
        id="group-select"
        v-model="selectedGroupId"
        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-80 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
      >
        <option v-if="loading" disabled>Loading...</option>
        <option
          v-for="group in groups"
          :key="group.id"
          :value="group.id"
        >
          {{ group.name }} (level {{ group.level }})
        </option>
      </select>
    </div>

    <!-- No group selected -->
    <div v-if="!selectedGroup" class="text-sm text-gray-500 dark:text-gray-400 py-4">
      Select a group to view its permissions.
    </div>

    <!-- Permissions Grid -->
    <div v-else class="space-y-2">
      <div
        v-for="section in sections"
        :key="section.id"
        class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden"
      >
        <!-- Section Header -->
        <div
          class="flex items-center justify-between px-4 py-3 bg-gray-50 dark:bg-gray-700 cursor-pointer"
          @click="section.permissions.length > 0 && toggleSection(section.id)"
        >
          <div class="flex items-center gap-3">
            <!-- Expand/collapse icon -->
            <svg
              v-if="section.permissions.length > 0"
              class="w-4 h-4 text-gray-500 dark:text-gray-400 transition-transform"
              :class="{ 'rotate-90': expandedSections.has(section.id) }"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
            </svg>
            <div v-else class="w-4"></div>

            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ section.label }}</span>
          </div>

          <!-- Section access toggle -->
          <div class="flex items-center gap-2" @click.stop>
            <span class="text-xs text-gray-500 dark:text-gray-400">Access</span>
            <label class="relative inline-flex items-center cursor-pointer">
              <input
                type="checkbox"
                :checked="hasPermission(section.sectionResource, 'read')"
                @change="handlePermissionChange"
                class="sr-only peer"
              />
              <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 dark:peer-focus:ring-primary-800 rounded-full peer dark:bg-gray-600 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:inset-s-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:after:border-gray-500 peer-checked:bg-primary-600"></div>
            </label>
          </div>
        </div>

        <!-- Section Permissions (expandable) -->
        <div
          v-if="section.permissions.length > 0 && expandedSections.has(section.id)"
          class="px-4 py-3 space-y-3 border-t border-gray-200 dark:border-gray-700"
        >
          <div
            v-for="perm in section.permissions"
            :key="perm.id"
            class="flex items-center justify-between pl-7"
          >
            <span class="text-sm text-gray-700 dark:text-gray-300">{{ perm.label }}</span>
            <div class="flex items-center gap-2">
              <span class="text-xs text-gray-400 dark:text-gray-500 font-mono">{{ perm.resource }}: {{ perm.action }}</span>
              <input
                type="checkbox"
                :checked="hasPermission(perm.resource, perm.action)"
                @change="handlePermissionChange"
                class="w-4 h-4 text-primary-600 bg-gray-100 border-gray-300 rounded-sm focus:ring-primary-500 dark:focus:ring-primary-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
              />
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Info banner -->
    <div v-if="selectedGroup" class="mt-6 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
      <div class="flex items-start gap-3">
        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z" clip-rule="evenodd"></path>
        </svg>
        <div>
          <p class="text-sm text-blue-800 dark:text-blue-300 font-medium">Section-based permissions</p>
          <p class="text-sm text-blue-700 dark:text-blue-400 mt-1">
            Section access controls visibility in the sidebar navigation. Resource permissions control what actions users can perform within each section. Permissions management will be available in a future update.
          </p>
        </div>
      </div>
    </div>
  </Card>
</template>
