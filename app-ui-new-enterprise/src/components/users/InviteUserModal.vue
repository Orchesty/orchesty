<script setup lang="ts">
import { ref, watch, computed, onMounted } from 'vue'
import Modal from '@/components/ui/Modal.vue'
import Button from '@/components/ui/Button.vue'
import Confirm from '@/components/ui/Confirm.vue'
import { inviteUsers, removeUser, type InviteResult } from '@/services/usersService'
import {
  fetchGroups,
  fetchPresets,
  fetchUserGroups,
  ensurePresetGroups,
  addUserToGroup,
  removeUserFromGroup,
} from '@/services/groupsService'
import type { PresetDefinition } from '@/services/groupsService'
import type { User, Group } from '@/types/users'
import { useToast } from '@/composables/useToast'

const { showToast } = useToast()

interface Props {
  modelValue: boolean
  user?: User | null
}

const props = withDefaults(defineProps<Props>(), {
  user: null,
})
const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  'user-invited': []
  'user-updated': []
  'user-removed': []
}>()

const isEditMode = computed(() => props.user != null)

const emailInput = ref('')
const submitting = ref(false)
const result = ref<InviteResult | null>(null)
const addedDirectly = ref(false)
const copied = ref(false)
const errorMessage = ref('')
const confirmRemoveOpen = ref(false)

const allGroups = ref<Group[]>([])
const presets = ref<PresetDefinition[]>([])
const selectedRole = ref<string>('monitoring')
const selectedGroupIds = ref<string[]>([])
const groupsLoading = ref(false)

const savedRole = ref<string>('')
const savedGroupIds = ref<string[]>([])

const accessGroups = computed(() =>
  allGroups.value.filter((g) => g.preset == null),
)

const showResults = computed(() =>
  !isEditMode.value && (result.value !== null || addedDirectly.value),
)

const isDirty = computed(() => {
  if (!isEditMode.value) return false
  if (selectedRole.value !== savedRole.value) return true
  const current = [...selectedGroupIds.value].sort().join(',')
  const saved = [...savedGroupIds.value].sort().join(',')
  return current !== saved
})

const modalTitle = computed(() => {
  if (isEditMode.value) return 'Edit user'
  if (addedDirectly.value) return 'User added'
  if (showResults.value) return 'Invite link'
  return 'Invite user'
})

onMounted(loadData)

watch(() => props.modelValue, (newValue) => {
  if (!newValue) {
    resetState()
  } else {
    loadData()
  }
})

function resetState() {
  emailInput.value = ''
  result.value = null
  addedDirectly.value = false
  copied.value = false
  errorMessage.value = ''
  selectedRole.value = 'monitoring'
  selectedGroupIds.value = []
  savedRole.value = ''
  savedGroupIds.value = []
}

async function loadData() {
  groupsLoading.value = true
  try {
    await ensurePresetGroups()
    const [groupsResp, presetsResp] = await Promise.all([
      fetchGroups(),
      fetchPresets(),
    ])
    allGroups.value = groupsResp.items
    presets.value = presetsResp

    if (isEditMode.value && props.user) {
      await loadUserState(props.user.id)
    }
  } catch {
    allGroups.value = []
    presets.value = []
  } finally {
    groupsLoading.value = false
  }
}

async function loadUserState(userId: string) {
  try {
    const response = await fetchUserGroups(userId)
    const userGroups = response.items

    const presetGroup = userGroups.find((g) => g.preset != null)
    selectedRole.value = presetGroup?.preset ?? ''
    savedRole.value = selectedRole.value

    const userAccessIds = userGroups
      .filter((g) => g.preset == null)
      .map((g) => g.id)
    selectedGroupIds.value = [...userAccessIds]
    savedGroupIds.value = [...userAccessIds]
  } catch {
    selectedRole.value = ''
    savedRole.value = ''
    selectedGroupIds.value = []
    savedGroupIds.value = []
  }
}

function findRoleGroupId(presetName: string): string | undefined {
  return allGroups.value.find((g) => g.preset === presetName)?.id
}

function toggleGroup(id: string) {
  const idx = selectedGroupIds.value.indexOf(id)
  if (idx === -1) {
    selectedGroupIds.value.push(id)
  } else {
    selectedGroupIds.value.splice(idx, 1)
  }
}

const isValidEmail = (email: string): boolean => {
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)
}

const canSubmitInvite = computed(() => {
  return emailInput.value.trim() !== '' && isValidEmail(emailInput.value.trim()) && !submitting.value
})

const getInviteLink = (hash: string): string => {
  return `${window.location.origin}/accept-invite/${hash}`
}

const copyLink = async () => {
  if (!result.value?.hash) return
  try {
    await navigator.clipboard.writeText(getInviteLink(result.value.hash))
    copied.value = true
    setTimeout(() => { copied.value = false }, 2000)
  } catch {
    showToast('Failed to copy link', 'error')
  }
}

const handleInviteSubmit = async () => {
  const email = emailInput.value.trim().toLowerCase()
  if (!email || !isValidEmail(email)) return

  submitting.value = true
  errorMessage.value = ''
  try {
    const groupIds = [...selectedGroupIds.value]
    const roleGroupId = findRoleGroupId(selectedRole.value)
    if (roleGroupId) {
      groupIds.unshift(roleGroupId)
    }

    const results = await inviteUsers([email], groupIds)
    const r = results[0]
    if (!r) {
      errorMessage.value = 'Failed to create invitation'
      return
    }
    if (r.hash) {
      result.value = r
      emit('user-invited')
    } else if (r.added) {
      addedDirectly.value = true
      emit('user-invited')
    } else {
      errorMessage.value = r.error || 'Failed to create invitation'
    }
  } catch (error) {
    console.error('Failed to invite user:', error)
    errorMessage.value = 'Failed to create invitation'
  } finally {
    submitting.value = false
  }
}

const handleEditSave = async () => {
  if (!props.user) return
  submitting.value = true
  try {
    const userId = props.user.id

    if (selectedRole.value !== savedRole.value) {
      const oldRoleGroupId = savedRole.value ? findRoleGroupId(savedRole.value) : undefined
      const newRoleGroupId = selectedRole.value ? findRoleGroupId(selectedRole.value) : undefined
      if (oldRoleGroupId) {
        await removeUserFromGroup(oldRoleGroupId, userId)
      }
      if (newRoleGroupId) {
        await addUserToGroup(newRoleGroupId, userId)
      }
    }

    const toAdd = selectedGroupIds.value.filter((id) => !savedGroupIds.value.includes(id))
    const toRemove = savedGroupIds.value.filter((id) => !selectedGroupIds.value.includes(id))

    await Promise.all([
      ...toAdd.map((gid) => addUserToGroup(gid, userId)),
      ...toRemove.map((gid) => removeUserFromGroup(gid, userId)),
    ])

    showToast('User updated successfully.', 'success')
    emit('user-updated')
    handleClose()
  } catch (error) {
    console.error('Failed to update user:', error)
    showToast('Failed to update user.', 'error')
  } finally {
    submitting.value = false
  }
}

const handleRemoveUser = async () => {
  if (!props.user) return
  try {
    await removeUser(props.user.id)
    confirmRemoveOpen.value = false
    showToast('User removed successfully.', 'success')
    emit('user-removed')
    handleClose()
  } catch (error) {
    console.error('Failed to remove user:', error)
    showToast('Failed to remove user.', 'error')
  }
}

const handleClose = () => {
  emit('update:modelValue', false)
}
</script>

<template>
  <Modal
    :model-value="modelValue"
    id="user-modal"
    :title="modalTitle"
    size="md"
    @update:model-value="emit('update:modelValue', $event)"
  >
    <!-- Form (invite or edit) -->
    <template v-if="!showResults">
      <form @submit.prevent="isEditMode ? handleEditSave() : handleInviteSubmit()">
        <!-- Email -->
        <div class="w-full mb-4">
          <label for="user-email-input" class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">
            Email address
          </label>
          <input
            v-if="isEditMode"
            type="email"
            id="user-email-input"
            :value="user!.email"
            disabled
            class="block w-full rounded-lg border border-gray-200 bg-gray-100 p-2.5 text-sm text-gray-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 cursor-not-allowed"
          />
          <input
            v-else
            v-model="emailInput"
            type="email"
            id="user-email-input"
            placeholder="user@example.com"
            class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
            :class="{ 'border-red-500 dark:border-red-400': errorMessage }"
          />
          <p v-if="errorMessage" class="mt-2 text-xs text-red-600 dark:text-red-400">
            {{ errorMessage }}
          </p>
        </div>

        <!-- Role selection -->
        <div class="w-full mb-4">
          <label for="role-select" class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">
            Role
          </label>
          <div v-if="groupsLoading" class="text-sm text-gray-500 dark:text-gray-400">
            Loading...
          </div>
          <select
            v-else
            id="role-select"
            v-model="selectedRole"
            class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:focus:border-primary-500 dark:focus:ring-primary-500"
          >
            <option v-for="preset in presets" :key="preset.name" :value="preset.name">
              {{ preset.label }}
            </option>
          </select>
        </div>

        <!-- Access group selection -->
        <div v-if="accessGroups.length > 0" class="w-full mb-4">
          <label class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">
            Access groups
          </label>
          <div class="flex flex-wrap gap-2">
            <button
              v-for="group in accessGroups"
              :key="group.id"
              type="button"
              @click="toggleGroup(group.id)"
              class="inline-flex items-center rounded-lg border px-3 py-1.5 text-xs font-medium transition-colors"
              :class="selectedGroupIds.includes(group.id)
                ? 'border-primary-500 bg-primary-50 text-primary-700 dark:border-primary-400 dark:bg-primary-900/30 dark:text-primary-300'
                : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600'"
            >
              <svg
                v-if="selectedGroupIds.includes(group.id)"
                class="mr-1.5 h-3 w-3"
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
              {{ group.name }}
            </button>
          </div>
        </div>
      </form>
    </template>

    <!-- User re-activated (invite mode only) -->
    <template v-else-if="addedDirectly">
      <div class="flex flex-col items-center py-4">
        <svg class="mb-3 h-12 w-12 text-green-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <p class="text-sm font-medium text-gray-900 dark:text-white">
          User access restored
        </p>
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
          {{ emailInput }} can now sign in to this instance again.
        </p>
      </div>
    </template>

    <!-- Invite link result (invite mode only) -->
    <template v-else>
      <div class="mb-3 flex items-start gap-2 rounded-lg border border-green-200 bg-green-50 p-3 dark:border-green-800 dark:bg-green-900/20">
        <svg class="mt-0.5 h-4 w-4 shrink-0 text-green-600 dark:text-green-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M22 2 11 13" /><path d="m22 2-7 20-4-9-9-4 20-7z" />
        </svg>
        <p class="text-sm text-green-700 dark:text-green-300">
          An invitation email has been sent to <strong>{{ result!.email }}</strong>.
        </p>
      </div>
      <p class="mb-3 text-sm text-gray-500 dark:text-gray-400">
        You can also share this link directly with the user.
      </p>

      <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-700">
        <div class="mb-2 text-sm font-medium text-gray-900 dark:text-white">
          {{ result!.email }}
        </div>
        <div class="flex items-center gap-2">
          <input
            type="text"
            readonly
            :value="getInviteLink(result!.hash!)"
            class="flex-1 rounded-md border border-gray-300 bg-gray-50 px-2.5 py-1.5 text-xs font-mono text-gray-700 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300"
            @click="($event.target as HTMLInputElement).select()"
          />
          <button
            type="button"
            @click="copyLink"
            class="inline-flex items-center gap-1 rounded-md border border-gray-300 bg-white px-2.5 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600"
          >
            <svg v-if="copied" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-3.5 w-3.5 text-primary-500">
              <polyline points="20 6 9 17 4 12" />
            </svg>
            <svg v-else xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-3.5 w-3.5">
              <rect width="14" height="14" x="8" y="8" rx="2" ry="2" /><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2" />
            </svg>
            {{ copied ? 'Copied' : 'Copy' }}
          </button>
        </div>
      </div>
    </template>

    <template #footer-actions>
      <!-- Edit mode footer -->
      <template v-if="isEditMode">
        <Button variant="danger" @click="confirmRemoveOpen = true" :disabled="submitting">
          Remove user
        </Button>
        <div class="flex-1" />
        <Button variant="outline" @click="handleClose" :disabled="submitting">
          Cancel
        </Button>
        <Button @click="handleEditSave" :disabled="!isDirty || submitting">
          {{ submitting ? 'Saving...' : 'Save' }}
        </Button>
      </template>

      <!-- Invite mode footer -->
      <template v-else-if="!showResults">
        <Button variant="outline" @click="handleClose" :disabled="submitting">
          Cancel
        </Button>
        <Button @click="handleInviteSubmit" :disabled="!canSubmitInvite">
          {{ submitting ? 'Generating...' : 'Generate invite link' }}
        </Button>
      </template>

      <!-- Result footer -->
      <template v-else>
        <Button variant="primary" @click="handleClose">
          Done
        </Button>
      </template>
    </template>
  </Modal>

  <!-- Remove confirm -->
  <Confirm
    v-if="isEditMode && user"
    v-model="confirmRemoveOpen"
    id="confirm-remove-user-modal"
    confirm-text="Yes, remove"
    confirm-variant="danger"
    @confirm="handleRemoveUser"
    @cancel="confirmRemoveOpen = false"
  >
    <p class="text-sm text-gray-700 dark:text-gray-300">
      Are you sure you want to remove <strong>{{ user.email }}</strong> from the system? This action cannot be undone.
    </p>
  </Confirm>
</template>
