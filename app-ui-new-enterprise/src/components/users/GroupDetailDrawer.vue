<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import Drawer from '@/components/ui/Drawer.vue'
import Button from '@/components/ui/Button.vue'
import Confirm from '@/components/ui/Confirm.vue'
import EditGroupModal from '@/components/users/EditGroupModal.vue'
import {
  fetchGroupDetail,
  removeGroup,
  addUserToGroup,
  removeUserFromGroup,
} from '@/services/groupsService'
import { fetchUsers } from '@/services/usersService'
import type { Group, GroupUser, GroupRule, User } from '@/types/users'

interface Props {
  modelValue: boolean
  group: Group | null
}

const props = defineProps<Props>()
const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  'group-updated': []
  'group-removed': []
}>()

const confirmRemoveOpen = ref(false)
const editModalOpen = ref(false)
const addUserDropdownOpen = ref(false)
const loadingDetail = ref(false)

const groupDetail = ref<Group | null>(null)
const groupUsers = ref<GroupUser[]>([])
const groupRules = ref<GroupRule[]>([])

const allUsers = ref<User[]>([])
const loadingUsers = ref(false)

const availableUsers = computed(() => {
  const assignedIds = new Set(groupUsers.value.map((u) => u.id))
  return allUsers.value.filter((u) => !assignedIds.has(u.id))
})

watch(
  () => props.modelValue,
  async (open) => {
    if (open && props.group) {
      await loadGroupDetail(props.group.id)
    } else {
      groupDetail.value = null
      groupUsers.value = []
      groupRules.value = []
    }
  },
)

async function loadGroupDetail(id: string) {
  loadingDetail.value = true
  try {
    const detail = await fetchGroupDetail(id)
    groupDetail.value = detail
    groupUsers.value = detail.users ?? []
    groupRules.value = detail.rules ?? []
  } catch (error) {
    console.error('Failed to load group detail:', error)
  } finally {
    loadingDetail.value = false
  }
}

async function loadAllUsers() {
  if (allUsers.value.length > 0) return
  loadingUsers.value = true
  try {
    const response = await fetchUsers({ limit: 1000 })
    allUsers.value = response.data
  } catch (error) {
    console.error('Failed to load users:', error)
  } finally {
    loadingUsers.value = false
  }
}

function handleToggleAddUser() {
  addUserDropdownOpen.value = !addUserDropdownOpen.value
  if (addUserDropdownOpen.value) {
    loadAllUsers()
  }
}

async function handleAddUser(userId: string) {
  if (!groupDetail.value) return
  try {
    await addUserToGroup(groupDetail.value.id, userId)
    addUserDropdownOpen.value = false
    await loadGroupDetail(groupDetail.value.id)
    emit('group-updated')
  } catch (error) {
    console.error('Failed to add user to group:', error)
  }
}

async function handleRemoveUser(userId: string) {
  if (!groupDetail.value) return
  try {
    await removeUserFromGroup(groupDetail.value.id, userId)
    await loadGroupDetail(groupDetail.value.id)
    emit('group-updated')
  } catch (error) {
    console.error('Failed to remove user from group:', error)
  }
}

function handleEditGroup() {
  editModalOpen.value = true
}

async function handleGroupUpdated() {
  if (groupDetail.value) {
    await loadGroupDetail(groupDetail.value.id)
  }
  emit('group-updated')
}

async function handleConfirmRemove() {
  if (!groupDetail.value) return
  try {
    await removeGroup(groupDetail.value.id)
    confirmRemoveOpen.value = false
    emit('group-removed')
  } catch (error) {
    console.error('Failed to remove group:', error)
  }
}

function formatActions(actions: string[]): string {
  return actions.join(', ')
}
</script>

<template>
  <Drawer
    :model-value="modelValue"
    id="group-detail-drawer"
    label="Group Details"
    width="w-[500px]"
    @update:model-value="emit('update:modelValue', $event)"
  >
    <!-- Header with group name and Edit button -->
    <template #header-actions>
      <div v-if="groupDetail" class="flex items-center justify-between">
        <h3 class="text-xl font-semibold text-gray-900 dark:text-white">{{ groupDetail.name }}</h3>
        <Button variant="outline" @click="handleEditGroup">
          <svg class="h-4 w-4 mr-1.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m14.304 4.844 2.852 2.852M7 7H4a1 1 0 0 0-1 1v10a1 1 0 0 0 1 1h11a1 1 0 0 0 1-1v-4.5m2.409-9.91a2.017 2.017 0 0 1 0 2.853l-6.844 6.844L8 14l.713-3.565 6.844-6.844a2.015 2.015 0 0 1 2.852 0Z"/>
          </svg>
          Edit
        </Button>
      </div>
    </template>

    <!-- Loading -->
    <div v-if="loadingDetail" class="flex items-center justify-center py-8">
      <svg class="animate-spin h-6 w-6 text-primary-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
      </svg>
    </div>

    <div v-else-if="groupDetail" class="space-y-6">
      <!-- Group Information -->
      <div class="bg-white dark:bg-gray-800 shadow-xs rounded-lg">
        <div class="py-3 mb-4 border-b border-gray-200 dark:border-gray-700">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Group Information</h3>
        </div>
        <div class="space-y-4">
          <dl class="flex items-center justify-between gap-4">
            <dt class="text-sm font-normal text-gray-500 dark:text-gray-400">Name</dt>
            <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ groupDetail.name }}</dd>
          </dl>
          <dl class="flex items-center justify-between gap-4">
            <dt class="text-sm font-normal text-gray-500 dark:text-gray-400">Level</dt>
            <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ groupDetail.level }}</dd>
          </dl>
          <dl class="flex items-center justify-between gap-4">
            <dt class="text-sm font-normal text-gray-500 dark:text-gray-400">Users</dt>
            <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ groupUsers.length }}</dd>
          </dl>
        </div>
      </div>

      <!-- Rules (read-only) -->
      <div class="bg-white dark:bg-gray-800 shadow-xs rounded-lg">
        <div class="py-3 mb-4 border-b border-gray-200 dark:border-gray-700">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Permissions</h3>
        </div>
        <div v-if="groupRules.length > 0" class="space-y-2">
          <div
            v-for="rule in groupRules"
            :key="rule.resource"
            class="flex items-center justify-between gap-4 py-1"
          >
            <span class="text-sm font-medium text-gray-900 dark:text-white capitalize">{{ rule.resource.replace('_', ' ') }}</span>
            <span class="text-xs text-gray-500 dark:text-gray-400">{{ formatActions(rule.actions) }}</span>
          </div>
        </div>
        <div v-else class="text-sm text-gray-500 dark:text-gray-400 py-2">
          No permissions assigned
        </div>
      </div>

      <!-- Users -->
      <div class="bg-white dark:bg-gray-800 shadow-xs rounded-lg">
        <div class="py-3 mb-4 border-b border-gray-200 dark:border-gray-700">
          <div class="flex items-center justify-between gap-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Users</h3>
            <div class="relative">
              <button
                @click="handleToggleAddUser"
                class="flex shrink-0 items-center text-sm font-medium text-primary-700 hover:underline dark:text-primary-500"
              >
                <svg class="me-1 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                  <path fill-rule="evenodd" d="M2 12a10 10 0 1 1 20 0 10 10 0 0 1-20 0Zm11-4.2a1 1 0 1 0-2 0V11H7.8a1 1 0 1 0 0 2H11v3.2a1 1 0 1 0 2 0V13h3.2a1 1 0 1 0 0-2H13V7.8Z" clip-rule="evenodd"></path>
                </svg>
                Add user
              </button>
              <div
                v-show="addUserDropdownOpen"
                class="absolute right-0 top-full mt-1 w-56 bg-white dark:bg-gray-700 rounded-lg shadow-lg border border-gray-200 dark:border-gray-600 z-10 max-h-48 overflow-y-auto"
              >
                <div v-if="loadingUsers" class="p-3 text-center text-sm text-gray-500">Loading...</div>
                <ul v-else-if="availableUsers.length > 0" class="p-2">
                  <li
                    v-for="user in availableUsers"
                    :key="user.id"
                    @click="handleAddUser(user.id)"
                    class="px-3 py-2 text-sm text-gray-900 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600 rounded-sm cursor-pointer"
                  >
                    {{ user.email }}
                  </li>
                </ul>
                <div v-else class="p-3 text-center text-sm text-gray-500">No users available</div>
              </div>
            </div>
          </div>
        </div>
        <div class="space-y-1">
          <div
            v-for="user in groupUsers"
            :key="user.id"
            class="flex items-center justify-between gap-4 py-1 hover:bg-gray-100 dark:hover:bg-gray-700"
          >
            <span class="text-sm text-gray-900 dark:text-gray-300">{{ user.email }}</span>
            <button
              type="button"
              @click="handleRemoveUser(user.id)"
              class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-6 h-6 inline-flex items-center justify-center dark:hover:bg-gray-600 dark:hover:text-white"
            >
              <svg class="w-4 h-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 7h14m-9 3v8m4-8v8M10 3h4a1 1 0 0 1 1 1v3H9V4a1 1 0 0 1 1-1ZM6 7h12v13a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V7Z"/>
              </svg>
            </button>
          </div>
          <div v-if="groupUsers.length === 0" class="text-sm text-gray-500 dark:text-gray-400 py-2">
            No users assigned
          </div>
        </div>
      </div>
    </div>

    <template #footer-actions>
      <Button variant="outline" @click="emit('update:modelValue', false)">
        Close
      </Button>
      <Button variant="danger" @click="confirmRemoveOpen = true">
        Remove
      </Button>
    </template>
  </Drawer>

  <EditGroupModal
    v-model="editModalOpen"
    :group="groupDetail"
    @group-updated="handleGroupUpdated"
  />

  <Confirm
    v-if="groupDetail"
    v-model="confirmRemoveOpen"
    id="confirm-remove-group-modal"
    confirm-text="Yes, remove"
    cancel-text="Cancel"
    @confirm="handleConfirmRemove"
    @cancel="confirmRemoveOpen = false"
  >
    <p class="text-sm text-gray-700 dark:text-gray-300">
      Are you sure you want to remove <strong>{{ groupDetail.name }}</strong>? This action cannot be undone.
    </p>
  </Confirm>
</template>
