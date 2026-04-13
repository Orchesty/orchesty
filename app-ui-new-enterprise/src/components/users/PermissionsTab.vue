<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import Card from '@/components/ui/Card.vue'
import Button from '@/components/ui/Button.vue'
import {
  fetchGroups,
  fetchPresets,
  updateGroupRules,
  fetchTopologyList,
} from '@/services/groupsService'
import type { PresetDefinition, RulePayload } from '@/services/groupsService'
import type { Group } from '@/types/users'
import { useToast } from '@/composables/useToast'

const { showToast } = useToast()

const groups = ref<Group[]>([])
const selectedGroupId = ref<string | null>(null)
const loading = ref(false)
const saving = ref(false)
const presets = ref<PresetDefinition[]>([])
const topologies = ref<{ id: string; name: string }[]>([])

const selectedGroup = computed(() =>
  groups.value.find((g) => g.id === selectedGroupId.value) ?? null,
)

const isPresetGroup = computed(() => selectedGroup.value?.preset != null)

const matchedPreset = computed(() =>
  presets.value.find((p) => p.name === selectedGroup.value?.preset) ?? null,
)

const topologyRunList = ref<{ id: string; name: string }[]>([])
const savedTopologyRunSnapshot = ref<string>('[]')
const topologyToAdd = ref<string>('')

const isDirty = computed(
  () => JSON.stringify(topologyRunList.value) !== savedTopologyRunSnapshot.value,
)

const availableTopologies = computed(() =>
  topologies.value.filter(
    (t) => !topologyRunList.value.some((tr) => tr.name === t.name),
  ),
)

function syncState() {
  const group = selectedGroup.value
  if (!group) {
    topologyRunList.value = []
    savedTopologyRunSnapshot.value = '[]'
    return
  }

  const runTopologies: { id: string; name: string }[] = []
  for (const rule of group.rules) {
    if (rule.resource.startsWith('topology:') && rule.actions.includes('run')) {
      const topoName = rule.resource.slice('topology:'.length)
      const matchedTopo = topologies.value.find((t) => t.name === topoName)
      runTopologies.push({
        id: matchedTopo?.id ?? topoName,
        name: topoName,
      })
    }
  }

  topologyRunList.value = runTopologies
  savedTopologyRunSnapshot.value = JSON.stringify(runTopologies)
}

watch(selectedGroupId, () => syncState())

function addTopologyRun() {
  if (!topologyToAdd.value) return
  const topo = topologies.value.find((t) => t.id === topologyToAdd.value)
  if (!topo) return
  if (topologyRunList.value.some((t) => t.name === topo.name)) return

  topologyRunList.value.push({ id: topo.id, name: topo.name })
  topologyToAdd.value = ''
}

function removeTopologyRun(name: string) {
  topologyRunList.value = topologyRunList.value.filter((t) => t.name !== name)
}

async function handleSave() {
  if (!selectedGroupId.value || !selectedGroup.value) return

  saving.value = true
  try {
    const topologyRules: RulePayload[] = topologyRunList.value.map((t) => ({
      resource: `topology:${t.name}`,
      actions: ['run'],
    }))

    const updatedGroup = await updateGroupRules(selectedGroupId.value, topologyRules)

    const idx = groups.value.findIndex((g) => g.id === updatedGroup.id)
    if (idx !== -1) {
      groups.value[idx] = updatedGroup
    }

    syncState()
    showToast('Permissions saved successfully.', 'success')
  } catch (error) {
    console.error('Failed to save permissions:', error)
    showToast('Failed to save permissions.', 'error')
  } finally {
    saving.value = false
  }
}

function handleDiscard() {
  syncState()
}

async function loadData() {
  loading.value = true
  try {
    const [groupsResp, presetsResp, topoResp] = await Promise.all([
      fetchGroups(),
      fetchPresets(),
      fetchTopologyList(),
    ])

    groups.value = groupsResp.items
    presets.value = presetsResp
    topologies.value = topoResp

    if (groupsResp.items.length > 0 && !selectedGroupId.value) {
      selectedGroupId.value = groupsResp.items[0]!.id
    }
    syncState()
  } catch (error) {
    console.error('Failed to load permissions data:', error)
  } finally {
    loading.value = false
  }
}

onMounted(() => loadData())
</script>

<template>
  <Card>
    <div class="mb-6">
      <div class="flex items-center justify-between mb-2">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Permissions</h3>

        <div v-if="selectedGroup && !isPresetGroup" class="flex items-center gap-2">
          <Button v-if="isDirty" variant="outline" @click="handleDiscard">Discard</Button>
          <Button :loading="saving" :disabled="!isDirty" @click="handleSave">Save</Button>
        </div>
      </div>
      <p class="text-sm text-gray-500 dark:text-gray-400">
        System preset groups have fixed permissions defined by their access level.
        Access groups hold per-topology run permissions.
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
        <option v-for="group in groups" :key="group.id" :value="group.id">
          {{ group.name }}
          <template v-if="group.preset"> ({{ group.preset }})</template>
        </option>
      </select>
    </div>

    <!-- No group selected -->
    <div v-if="!selectedGroup" class="text-sm text-gray-500 dark:text-gray-400 py-4">
      Select a group to view its permissions.
    </div>

    <!-- Preset group view (read-only) -->
    <div v-else-if="isPresetGroup" class="space-y-4">
      <div class="flex items-center gap-2 mb-2">
        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary-100 text-primary-800 dark:bg-primary-900/30 dark:text-primary-300">
          System Preset
        </span>
        <span v-if="matchedPreset" class="text-sm font-semibold text-gray-900 dark:text-white">
          {{ matchedPreset.label }}
        </span>
      </div>

      <p v-if="matchedPreset" class="text-sm text-gray-500 dark:text-gray-400">
        {{ matchedPreset.description }}
      </p>

      <div class="text-xs text-gray-400 dark:text-gray-500 italic">
        Permissions for system preset groups are defined in code and cannot be changed.
      </div>

      <!-- Show resolved rules as read-only list -->
      <div v-if="matchedPreset" class="mt-3">
        <h4 class="text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider mb-2">
          Included permissions
        </h4>
        <div class="space-y-1">
          <div
            v-for="rule in matchedPreset.rules"
            :key="rule.resource"
            class="flex items-center justify-between px-3 py-1.5 bg-gray-50 dark:bg-gray-800 rounded"
          >
            <span class="text-sm text-gray-700 dark:text-gray-300 font-mono">{{ rule.resource }}</span>
            <div class="flex gap-1">
              <span
                v-for="action in rule.actions"
                :key="action"
                class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium"
                :class="
                  action === 'read'
                    ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300'
                    : action === 'write'
                      ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300'
                      : action === 'delete'
                        ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300'
                        : 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300'
                "
              >
                {{ action }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Access group view (editable per-topology RUN) -->
    <div v-else class="space-y-6">
      <div class="flex items-center gap-2 mb-2">
        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300">
          Access Group
        </span>
      </div>

      <div>
        <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">
          Per-Topology Run Permissions
        </h4>
        <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">
          Grant this group the ability to run specific topologies.
        </p>

        <div class="flex items-end gap-2 mb-3">
          <div class="flex-1">
            <select
              v-model="topologyToAdd"
              class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
            >
              <option value="">Select topology...</option>
              <option v-for="topo in availableTopologies" :key="topo.id" :value="topo.id">
                {{ topo.name }}
              </option>
            </select>
          </div>
          <Button :disabled="!topologyToAdd" variant="outline" @click="addTopologyRun">
            Add
          </Button>
        </div>

        <div v-if="topologyRunList.length === 0" class="text-sm text-gray-400 dark:text-gray-500 py-2">
          No per-topology run permissions assigned.
        </div>
        <div v-else class="space-y-1">
          <div
            v-for="topo in topologyRunList"
            :key="topo.name"
            class="flex items-center justify-between px-3 py-2 bg-gray-50 dark:bg-gray-800 rounded-lg"
          >
            <div class="flex items-center gap-2">
              <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <span class="text-sm text-gray-700 dark:text-gray-300">{{ topo.name }}</span>
            </div>
            <button
              class="text-gray-400 hover:text-red-500 transition-colors"
              @click="removeTopologyRun(topo.name)"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>
      </div>

      <!-- Unsaved changes warning -->
      <div
        v-if="isDirty"
        class="p-3 bg-amber-50 dark:bg-amber-900/20 rounded-lg border border-amber-200 dark:border-amber-800 flex items-center justify-between"
      >
        <p class="text-sm text-amber-800 dark:text-amber-300">
          You have unsaved changes.
        </p>
        <div class="flex items-center gap-2">
          <Button variant="outline" @click="handleDiscard">Discard</Button>
          <Button :loading="saving" @click="handleSave">Save</Button>
        </div>
      </div>
    </div>
  </Card>
</template>
