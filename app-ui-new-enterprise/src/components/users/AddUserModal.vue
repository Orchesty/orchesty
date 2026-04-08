<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import Modal from '@/components/ui/Modal.vue'
import Button from '@/components/ui/Button.vue'
import { searchAccountUsers, addUserFromAccount, type CloudAccountUser } from '@/services/cloudUsersService'
import { inviteUsers, type InviteResult } from '@/services/usersService'
import { fetchGroups } from '@/services/groupsService'
import type { Group } from '@/types/users'
import { useToast } from '@/composables/useToast'

const { showToast } = useToast()

interface Props {
  modelValue: boolean
  cloudMode?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  cloudMode: false,
})
const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  'user-added': []
}>()

type TabId = 'account' | 'invite'
const activeTab = ref<TabId>('account')

// --- Cloud account search state ---
const searchQuery = ref('')
const cloudUsers = ref<CloudAccountUser[]>([])
const cloudLoading = ref(false)
const adding = ref<string | null>(null)
let searchTimeout: ReturnType<typeof setTimeout> | null = null

// --- Invite state ---
const emailInput = ref('')
const submitting = ref(false)
const inviteResult = ref<InviteResult | null>(null)
const addedDirectly = ref(false)
const copied = ref(false)
const errorMessage = ref('')

const availableGroups = ref<Group[]>([])
const selectedGroupIds = ref<string[]>([])
const groupsLoading = ref(false)

const showInviteResult = computed(() => inviteResult.value !== null || addedDirectly.value)
const displayInviteLink = computed(() => {
  if (!inviteResult.value) return ''
  if (inviteResult.value.inviteLink) return inviteResult.value.inviteLink
  if (inviteResult.value.hash) return `${window.location.origin}/accept-invite/${inviteResult.value.hash}`
  return ''
})

const isValidEmail = (email: string): boolean => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)

const canSubmit = computed(() =>
  emailInput.value.trim() !== '' && isValidEmail(emailInput.value.trim()) && !submitting.value,
)

const modalTitle = computed(() => {
  if (!props.cloudMode) {
    return showInviteResult.value ? 'Invite link' : 'Add user'
  }
  return 'Add user'
})

watch(() => props.modelValue, (open) => {
  if (open) {
    activeTab.value = props.cloudMode ? 'account' : 'invite'
    resetAll()
    if (props.cloudMode) loadCloudUsers()
    loadAvailableGroups()
  }
})

function resetAll() {
  searchQuery.value = ''
  cloudUsers.value = []
  adding.value = null
  emailInput.value = ''
  submitting.value = false
  inviteResult.value = null
  addedDirectly.value = false
  copied.value = false
  errorMessage.value = ''
  selectedGroupIds.value = []
}

async function loadAvailableGroups() {
  groupsLoading.value = true
  try {
    const response = await fetchGroups()
    availableGroups.value = response.items
  } catch {
    availableGroups.value = []
  } finally {
    groupsLoading.value = false
  }
}

function toggleGroup(id: string) {
  const idx = selectedGroupIds.value.indexOf(id)
  if (idx === -1) {
    selectedGroupIds.value.push(id)
  } else {
    selectedGroupIds.value.splice(idx, 1)
  }
}

function switchTab(tab: TabId) {
  activeTab.value = tab
  if (tab === 'account' && cloudUsers.value.length === 0) loadCloudUsers()
}

// --- Cloud account methods ---
function handleSearchInput() {
  if (searchTimeout) clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => loadCloudUsers(), 300)
}

async function loadCloudUsers() {
  cloudLoading.value = true
  try {
    cloudUsers.value = await searchAccountUsers(searchQuery.value)
  } catch {
    cloudUsers.value = []
  } finally {
    cloudLoading.value = false
  }
}

async function addCloudUser(user: CloudAccountUser) {
  adding.value = user.id
  try {
    await addUserFromAccount(user.email, user.name)
    showToast(`${user.email} added successfully`, 'success')
    cloudUsers.value = cloudUsers.value.filter(u => u.id !== user.id)
    emit('user-added')
  } catch {
    showToast('Failed to add user', 'error')
  } finally {
    adding.value = null
  }
}

// --- Invite methods ---
async function copyLink() {
  const link = displayInviteLink.value
  if (!link) return
  try {
    await navigator.clipboard.writeText(link)
    copied.value = true
    setTimeout(() => { copied.value = false }, 2000)
  } catch {
    showToast('Failed to copy link', 'error')
  }
}

async function handleInviteSubmit() {
  const email = emailInput.value.trim().toLowerCase()
  if (!email || !isValidEmail(email)) return

  submitting.value = true
  errorMessage.value = ''
  try {
    const results = await inviteUsers([email], selectedGroupIds.value)
    const r = results[0]
    if (!r) {
      errorMessage.value = 'No response from server'
      return
    }
    if (r.hash || r.inviteLink) {
      inviteResult.value = r
      emit('user-added')
    } else if (r.added) {
      addedDirectly.value = true
      emit('user-added')
    } else {
      errorMessage.value = r.error || 'Failed to create invitation'
    }
  } catch {
    errorMessage.value = 'Failed to create invitation'
  } finally {
    submitting.value = false
  }
}

function handleClose() {
  emit('update:modelValue', false)
}
</script>

<template>
  <Modal
    :model-value="modelValue"
    id="add-user-modal"
    :title="modalTitle"
    size="lg"
    @update:model-value="emit('update:modelValue', $event)"
  >
    <!-- Tab bar (cloud mode only) -->
    <div v-if="cloudMode" class="mb-4 border-b border-gray-200 dark:border-gray-700">
      <ul class="-mb-px flex text-sm font-medium text-center">
        <li class="mr-2">
          <button
            type="button"
            class="inline-block rounded-t-lg border-b-2 px-4 py-2.5"
            :class="activeTab === 'account'
              ? 'border-primary-600 text-primary-600 dark:border-primary-500 dark:text-primary-500'
              : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-600 dark:text-gray-400 dark:hover:text-gray-300'"
            @click="switchTab('account')"
          >
            From account
          </button>
        </li>
        <li>
          <button
            type="button"
            class="inline-block rounded-t-lg border-b-2 px-4 py-2.5"
            :class="activeTab === 'invite'
              ? 'border-primary-600 text-primary-600 dark:border-primary-500 dark:text-primary-500'
              : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-600 dark:text-gray-400 dark:hover:text-gray-300'"
            @click="switchTab('invite')"
          >
            Invite by email
          </button>
        </li>
      </ul>
    </div>

    <!-- ====== Account tab content (cloud only) ====== -->
    <template v-if="cloudMode && activeTab === 'account'">
      <div class="mb-4">
        <p class="mb-3 text-sm text-gray-500 dark:text-gray-400">
          Add an existing user from your cloud account to this instance.
        </p>
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Search by name or email..."
          class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
          @input="handleSearchInput"
        />
      </div>

      <div v-if="cloudLoading" class="flex justify-center py-8">
        <svg class="h-8 w-8 animate-spin text-primary-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
        </svg>
      </div>

      <div v-else-if="cloudUsers.length === 0" class="py-8 text-center text-sm text-gray-500 dark:text-gray-400">
        {{ searchQuery ? 'No matching users found' : 'No available account users' }}
      </div>

      <div v-else class="max-h-80 overflow-y-auto">
        <ul class="divide-y divide-gray-200 dark:divide-gray-700">
          <li
            v-for="user in cloudUsers"
            :key="user.id"
            class="flex items-center justify-between px-1 py-3"
          >
            <div>
              <p class="text-sm font-medium text-gray-900 dark:text-white">{{ user.name }}</p>
              <p class="text-xs text-gray-500 dark:text-gray-400">{{ user.email }}</p>
            </div>
            <Button
              size="sm"
              @click="addCloudUser(user)"
              :disabled="adding === user.id"
            >
              {{ adding === user.id ? 'Adding...' : 'Add' }}
            </Button>
          </li>
        </ul>
      </div>
    </template>

    <!-- ====== Invite tab content ====== -->
    <template v-if="activeTab === 'invite'">
      <template v-if="!showInviteResult">
        <form @submit.prevent="handleInviteSubmit">
          <div class="w-full mb-4">
            <label for="add-user-invite-input" class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">
              Email address
            </label>
            <input
              v-model="emailInput"
              type="email"
              id="add-user-invite-input"
              placeholder="user@example.com"
              class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
              :class="{ 'border-red-500 dark:border-red-400': errorMessage }"
            />
            <p v-if="errorMessage" class="mt-2 text-xs text-red-600 dark:text-red-400">
              {{ errorMessage }}
            </p>
          </div>

          <!-- Group selection -->
          <div class="w-full mb-4">
            <label class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">
              Assign to groups
            </label>
            <div v-if="groupsLoading" class="text-sm text-gray-500 dark:text-gray-400">
              Loading groups...
            </div>
            <div v-else-if="availableGroups.length === 0" class="text-sm text-gray-500 dark:text-gray-400">
              No groups available
            </div>
            <div v-else class="flex flex-wrap gap-2">
              <button
                v-for="group in availableGroups"
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

      <!-- User was added directly (existing cloud user or re-activated) -->
      <template v-else-if="addedDirectly">
        <div class="flex flex-col items-center py-4">
          <svg class="mb-3 h-12 w-12 text-green-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <p class="text-sm font-medium text-gray-900 dark:text-white">
            User added to instance
          </p>
          <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
            {{ emailInput }} has been added and can access this instance.
          </p>
        </div>
      </template>

      <!-- Invite link generated -->
      <template v-else>
        <div class="mb-3 flex items-start gap-2 rounded-lg border border-green-200 bg-green-50 p-3 dark:border-green-800 dark:bg-green-900/20">
          <svg class="mt-0.5 h-4 w-4 shrink-0 text-green-600 dark:text-green-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M22 2 11 13" /><path d="m22 2-7 20-4-9-9-4 20-7z" />
          </svg>
          <p class="text-sm text-green-700 dark:text-green-300">
            An invitation email has been sent to <strong>{{ inviteResult!.email }}</strong>.
          </p>
        </div>
        <p class="mb-3 text-sm text-gray-500 dark:text-gray-400">
          {{ inviteResult?.inviteLink
            ? 'You can also share this link directly. They will sign in through the cloud portal.'
            : 'You can also share this link directly with the user.' }}
        </p>

        <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-700">
          <div class="mb-2 text-sm font-medium text-gray-900 dark:text-white">
            {{ inviteResult!.email }}
          </div>
          <div class="flex items-center gap-2">
            <input
              type="text"
              readonly
              :value="displayInviteLink"
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
    </template>

    <!-- ====== Footer ====== -->
    <template #footer-actions>
      <!-- Account tab footer -->
      <template v-if="cloudMode && activeTab === 'account'">
        <Button variant="outline" @click="handleClose">
          Close
        </Button>
      </template>

      <!-- Invite tab footer -->
      <template v-else-if="activeTab === 'invite'">
        <template v-if="!showInviteResult">
          <Button variant="outline" @click="handleClose" :disabled="submitting">
            Cancel
          </Button>
          <Button @click="handleInviteSubmit" :disabled="!canSubmit">
            {{ submitting ? 'Generating...' : 'Generate invite link' }}
          </Button>
        </template>
        <template v-else>
          <Button variant="primary" @click="handleClose">
            Done
          </Button>
        </template>
      </template>
    </template>
  </Modal>
</template>
