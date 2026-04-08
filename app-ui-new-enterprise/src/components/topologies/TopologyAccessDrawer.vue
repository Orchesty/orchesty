<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import Drawer from '@/components/ui/Drawer.vue'
import Button from '@/components/ui/Button.vue'
import {
  fetchGroups,
  fetchTopologyAccess,
  updateTopologyAccess,
} from '@/services/groupsService'
import type { TopologyAccessEntry } from '@/services/groupsService'
import type { Group } from '@/types/users'
import { useToast } from '@/composables/useToast'

const TOPOLOGY_ACTIONS = ['read', 'write', 'delete', 'run'] as const
type TopologyAction = (typeof TOPOLOGY_ACTIONS)[number]

const ACTION_LABELS: Record<TopologyAction, string> = {
  read: 'Read',
  write: 'Edit',
  delete: 'Delete',
  run: 'Run',
}

interface Props {
  modelValue: boolean
  topologyId: string
  topologyName?: string
}

interface LocalAccessEntry {
  groupId: string
  groupName: string
  actions: Set<TopologyAction>
}

const props = defineProps<Props>()
const emit = defineEmits<{
  'update:modelValue': [value: boolean]
}>()

const { showToast } = useToast()

const loading = ref(false)
const saving = ref(false)
const accessEntries = ref<LocalAccessEntry[]>([])
const savedSnapshot = ref<string>('[]')
const allGroups = ref<Group[]>([])
const groupToAdd = ref<string>('')

const availableGroups = computed(() =>
  allGroups.value
    .filter((g) => g.preset == null)
    .filter((g) => !accessEntries.value.some((e) => e.groupId === g.id)),
)

const isDirty = computed(() => {
  const current = accessEntries.value.map((e) => ({
    groupId: e.groupId,
    actions: [...e.actions].sort(),
  }))
  return JSON.stringify(current) !== savedSnapshot.value
})

function serializeSnapshot(): string {
  const data = accessEntries.value.map((e) => ({
    groupId: e.groupId,
    actions: [...e.actions].sort(),
  }))
  return JSON.stringify(data)
}

async function loadData() {
  loading.value = true
  try {
    const [accessResp, groupsResp] = await Promise.all([
      fetchTopologyAccess(props.topologyId),
      fetchGroups(),
    ])

    allGroups.value = groupsResp.items

    accessEntries.value = accessResp.map((entry: TopologyAccessEntry) => ({
      groupId: entry.groupId,
      groupName: entry.groupName,
      actions: new Set(entry.actions.filter((a): a is TopologyAction =>
        TOPOLOGY_ACTIONS.includes(a as TopologyAction),
      )),
    }))

    savedSnapshot.value = serializeSnapshot()
  } catch (error) {
    console.error('Failed to load topology access:', error)
  } finally {
    loading.value = false
  }
}

watch(() => props.modelValue, (isOpen) => {
  if (isOpen) {
    loadData()
  }
})

function addGroup() {
  if (!groupToAdd.value) return
  const group = allGroups.value.find((g) => g.id === groupToAdd.value)
  if (!group) return

  accessEntries.value.push({
    groupId: group.id,
    groupName: group.name,
    actions: new Set<TopologyAction>(['run']),
  })
  groupToAdd.value = ''
}

function removeGroup(groupId: string) {
  accessEntries.value = accessEntries.value.filter((e) => e.groupId !== groupId)
}

function toggleAction(groupId: string, action: TopologyAction) {
  const entry = accessEntries.value.find((e) => e.groupId === groupId)
  if (!entry) return

  if (entry.actions.has(action)) {
    entry.actions.delete(action)
  } else {
    entry.actions.add(action)
  }
}

async function handleSave() {
  saving.value = true
  try {
    const payload = accessEntries.value
      .filter((e) => e.actions.size > 0)
      .map((e) => ({
        groupId: e.groupId,
        actions: [...e.actions],
      }))

    const result = await updateTopologyAccess(props.topologyId, payload)

    accessEntries.value = result.map((entry: TopologyAccessEntry) => ({
      groupId: entry.groupId,
      groupName: entry.groupName,
      actions: new Set(entry.actions.filter((a): a is TopologyAction =>
        TOPOLOGY_ACTIONS.includes(a as TopologyAction),
      )),
    }))

    savedSnapshot.value = serializeSnapshot()
    showToast('Access permissions saved.', 'success')
  } catch (error) {
    console.error('Failed to save topology access:', error)
    showToast('Failed to save access permissions.', 'error')
  } finally {
    saving.value = false
  }
}

function handleDiscard() {
  loadData()
}

function handleClose() {
  emit('update:modelValue', false)
}
</script>

<template>
  <Drawer
    :model-value="modelValue"
    id="topology-access-drawer"
    label="ACCESS CONTROL"
    width="w-[480px]"
    placement="right"
    @update:model-value="emit('update:modelValue', $event)"
  >
    <!-- Loading State -->
    <div v-if="loading" class="flex items-center justify-center py-8">
      <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600"></div>
    </div>

    <template v-else>
      <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">
        Manage which groups have access to <strong class="text-gray-700 dark:text-gray-200">{{ topologyName }}</strong> and what actions they can perform.
      </p>

      <!-- Add group -->
      <div class="flex items-end gap-2 mb-5">
        <div class="flex-1">
          <label for="access-group-select" class="block mb-1 text-xs font-medium text-gray-700 dark:text-gray-300">
            Add group
          </label>
          <select
            id="access-group-select"
            v-model="groupToAdd"
            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
          >
            <option value="">Select group...</option>
            <option v-for="group in availableGroups" :key="group.id" :value="group.id">
              {{ group.name }}
            </option>
          </select>
        </div>
        <Button :disabled="!groupToAdd" variant="outline" size="sm" @click="addGroup">
          Add
        </Button>
      </div>

      <!-- Group list -->
      <div v-if="accessEntries.length === 0" class="text-sm text-gray-400 dark:text-gray-500 py-4 text-center">
        No groups have specific access to this topology.
      </div>

      <div v-else class="space-y-3">
        <div
          v-for="entry in accessEntries"
          :key="entry.groupId"
          class="rounded-lg border border-gray-200 dark:border-gray-700 p-3"
        >
          <div class="flex items-center justify-between mb-2">
            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ entry.groupName }}</span>
            <button
              type="button"
              class="text-gray-400 hover:text-red-500 transition-colors p-0.5"
              @click="removeGroup(entry.groupId)"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
          <div class="flex flex-wrap gap-2">
            <button
              v-for="action in TOPOLOGY_ACTIONS"
              :key="action"
              type="button"
              @click="toggleAction(entry.groupId, action)"
              class="inline-flex items-center rounded-md border px-2.5 py-1 text-xs font-medium transition-colors"
              :class="entry.actions.has(action)
                ? action === 'read'
                  ? 'border-blue-400 bg-blue-50 text-blue-700 dark:border-blue-600 dark:bg-blue-900/30 dark:text-blue-300'
                  : action === 'write'
                    ? 'border-green-400 bg-green-50 text-green-700 dark:border-green-600 dark:bg-green-900/30 dark:text-green-300'
                    : action === 'delete'
                      ? 'border-red-400 bg-red-50 text-red-700 dark:border-red-600 dark:bg-red-900/30 dark:text-red-300'
                      : 'border-amber-400 bg-amber-50 text-amber-700 dark:border-amber-600 dark:bg-amber-900/30 dark:text-amber-300'
                : 'border-gray-200 bg-white text-gray-400 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-500 dark:hover:bg-gray-700'"
            >
              <svg
                v-if="entry.actions.has(action)"
                class="mr-1 h-3 w-3"
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="3"
                stroke-linecap="round"
                stroke-linejoin="round"
              >
                <polyline points="20 6 9 17 4 12" />
              </svg>
              {{ ACTION_LABELS[action] }}
            </button>
          </div>
        </div>
      </div>

      <!-- Unsaved indicator -->
      <div
        v-if="isDirty"
        class="mt-4 p-2.5 bg-amber-50 dark:bg-amber-900/20 rounded-lg border border-amber-200 dark:border-amber-800"
      >
        <p class="text-xs text-amber-800 dark:text-amber-300">Unsaved changes</p>
      </div>
    </template>

    <template #footer-actions>
      <Button v-if="isDirty" variant="outline" @click="handleDiscard">Discard</Button>
      <Button v-if="isDirty" :loading="saving" @click="handleSave">Save</Button>
      <Button v-else variant="outline" @click="handleClose">Close</Button>
    </template>
  </Drawer>
</template>
