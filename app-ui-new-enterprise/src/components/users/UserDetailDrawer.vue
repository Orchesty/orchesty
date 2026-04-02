<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import Drawer from '@/components/ui/Drawer.vue'
import Button from '@/components/ui/Button.vue'
import Confirm from '@/components/ui/Confirm.vue'
import { removeUser } from '@/services/usersService'
import {
  fetchUserGroups,
  fetchGroups,
  addUserToGroup,
  removeUserFromGroup,
} from '@/services/groupsService'
import { useToast } from '@/composables/useToast'
import { useDateFormat } from '@/composables/useDateFormat'
import type { User, Group } from '@/types/users'

const { showToast } = useToast()
const { formatDateTime } = useDateFormat()

interface Props {
  modelValue: boolean
  user: User | null
}

const props = defineProps<Props>()
const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  'user-updated': []
  'user-removed': []
}>()

const confirmRemoveOpen = ref(false)
const addGroupDropdownOpen = ref(false)

const userGroups = ref<Group[]>([])
const allGroups = ref<Group[]>([])
const loadingGroups = ref(false)
const loadingAllGroups = ref(false)

const availableGroups = computed(() => {
  const assignedIds = new Set(userGroups.value.map((g) => g.id))
  return allGroups.value.filter((g) => !assignedIds.has(g.id))
})

watch(
  () => props.modelValue,
  async (open) => {
    if (open && props.user) {
      await loadUserGroups(props.user.id)
    } else {
      userGroups.value = []
      addGroupDropdownOpen.value = false
    }
  },
)

async function loadUserGroups(userId: string) {
  loadingGroups.value = true
  try {
    const response = await fetchUserGroups(userId)
    userGroups.value = response.items
  } catch (error) {
    console.error('Failed to load user groups:', error)
    userGroups.value = []
  } finally {
    loadingGroups.value = false
  }
}

async function loadAllGroups() {
  if (allGroups.value.length > 0) return
  loadingAllGroups.value = true
  try {
    const response = await fetchGroups()
    allGroups.value = response.items
  } catch (error) {
    console.error('Failed to load groups:', error)
  } finally {
    loadingAllGroups.value = false
  }
}

function handleToggleAddGroup() {
  addGroupDropdownOpen.value = !addGroupDropdownOpen.value
  if (addGroupDropdownOpen.value) {
    loadAllGroups()
  }
}

async function handleAddGroup(groupId: string) {
  if (!props.user) return
  try {
    await addUserToGroup(groupId, props.user.id)
    addGroupDropdownOpen.value = false
    await loadUserGroups(props.user.id)
    emit('user-updated')
  } catch (error) {
    console.error('Failed to add user to group:', error)
    showToast('Failed to add user to group', 'error')
  }
}

async function handleRemoveGroup(groupId: string) {
  if (!props.user) return
  try {
    await removeUserFromGroup(groupId, props.user.id)
    await loadUserGroups(props.user.id)
    emit('user-updated')
  } catch (error) {
    console.error('Failed to remove user from group:', error)
    showToast('Failed to remove user from group', 'error')
  }
}

const handleConfirmRemove = async () => {
  if (!props.user) return
  try {
    await removeUser(props.user.id)
    confirmRemoveOpen.value = false
    emit('user-removed')
    showToast('User removed successfully', 'success')
  } catch (error) {
    console.error('Failed to remove user:', error)
    showToast('Failed to remove user', 'error')
  }
}
</script>

<template>
  <Drawer
    :model-value="modelValue"
    id="user-detail-drawer"
    label="User Details"
    width="w-[500px]"
    @update:model-value="emit('update:modelValue', $event)"
  >
    <!-- Header with user email -->
    <template #header-actions>
      <div v-if="user">
        <h3 class="text-xl font-semibold text-gray-900 dark:text-white">{{ user.email }}</h3>
      </div>
    </template>

    <div v-if="user" class="space-y-6">
      <!-- User Information -->
      <div class="bg-white dark:bg-gray-800 shadow-xs rounded-lg">
        <div class="py-3 mb-4 border-b border-gray-200 dark:border-gray-700">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white">User Information</h3>
        </div>
        <div class="space-y-4">
          <dl class="flex items-center justify-between gap-4">
            <dt class="text-sm font-normal text-gray-500 dark:text-gray-400">Email</dt>
            <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ user.email }}</dd>
          </dl>
          <dl class="flex items-center justify-between gap-4">
            <dt class="text-sm font-normal text-gray-500 dark:text-gray-400">Created</dt>
            <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ formatDateTime(user.created) }}</dd>
          </dl>
        </div>
      </div>

      <!-- Groups -->
      <div class="bg-white dark:bg-gray-800 shadow-xs rounded-lg">
        <div class="py-3 mb-4 border-b border-gray-200 dark:border-gray-700">
          <div class="flex items-center justify-between gap-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Groups</h3>
            <div class="relative">
              <button
                @click="handleToggleAddGroup"
                class="flex shrink-0 items-center text-sm font-medium text-primary-700 hover:underline dark:text-primary-500"
              >
                <svg class="me-1 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                  <path fill-rule="evenodd" d="M2 12a10 10 0 1 1 20 0 10 10 0 0 1-20 0Zm11-4.2a1 1 0 1 0-2 0V11H7.8a1 1 0 1 0 0 2H11v3.2a1 1 0 1 0 2 0V13h3.2a1 1 0 1 0 0-2H13V7.8Z" clip-rule="evenodd"></path>
                </svg>
                Add to group
              </button>
              <div
                v-show="addGroupDropdownOpen"
                class="absolute right-0 top-full mt-1 w-56 bg-white dark:bg-gray-700 rounded-lg shadow-lg border border-gray-200 dark:border-gray-600 z-10 max-h-48 overflow-y-auto"
              >
                <div v-if="loadingAllGroups" class="p-3 text-center text-sm text-gray-500">Loading...</div>
                <ul v-else-if="availableGroups.length > 0" class="p-2">
                  <li
                    v-for="group in availableGroups"
                    :key="group.id"
                    @click="handleAddGroup(group.id)"
                    class="px-3 py-2 text-sm text-gray-900 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600 rounded-sm cursor-pointer"
                  >
                    {{ group.name }}
                    <span class="text-xs text-gray-400 dark:text-gray-500 ml-1">(level {{ group.level }})</span>
                  </li>
                </ul>
                <div v-else class="p-3 text-center text-sm text-gray-500">No groups available</div>
              </div>
            </div>
          </div>
        </div>

        <div v-if="loadingGroups" class="flex items-center justify-center py-4">
          <svg class="animate-spin h-5 w-5 text-primary-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
          </svg>
        </div>

        <div v-else class="space-y-1">
          <div
            v-for="group in userGroups"
            :key="group.id"
            class="flex items-center justify-between gap-4 py-1 hover:bg-gray-100 dark:hover:bg-gray-700"
          >
            <div>
              <span class="text-sm text-gray-900 dark:text-gray-300">{{ group.name }}</span>
              <span class="text-xs text-gray-400 dark:text-gray-500 ml-1">(level {{ group.level }})</span>
            </div>
            <button
              type="button"
              @click="handleRemoveGroup(group.id)"
              class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-6 h-6 inline-flex items-center justify-center dark:hover:bg-gray-600 dark:hover:text-white"
            >
              <svg class="w-4 h-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 7h14m-9 3v8m4-8v8M10 3h4a1 1 0 0 1 1 1v3H9V4a1 1 0 0 1 1-1ZM6 7h12v13a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V7Z"/>
              </svg>
            </button>
          </div>
          <div v-if="userGroups.length === 0" class="text-sm text-gray-500 dark:text-gray-400 py-2">
            No groups assigned
          </div>
        </div>
      </div>
    </div>

    <template #footer-actions>
      <div class="flex items-center justify-between w-full">
        <Button variant="danger" @click="confirmRemoveOpen = true">
          Remove
        </Button>
        <Button variant="outline" @click="emit('update:modelValue', false)">
          Close
        </Button>
      </div>
    </template>
  </Drawer>

  <Confirm
    v-if="user"
    v-model="confirmRemoveOpen"
    id="confirm-remove-user-modal"
    confirm-text="Yes, remove"
    cancel-text="Cancel"
    @confirm="handleConfirmRemove"
    @cancel="confirmRemoveOpen = false"
  >
    <p class="text-sm text-gray-700 dark:text-gray-300">
      Are you sure you want to remove <strong>{{ user.email }}</strong> from the system? This action cannot be undone.
    </p>
  </Confirm>
</template>
