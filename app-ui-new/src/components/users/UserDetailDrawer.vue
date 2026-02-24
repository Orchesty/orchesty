<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import Drawer from '@/components/ui/Drawer.vue'
import Button from '@/components/ui/Button.vue'
import Confirm from '@/components/ui/Confirm.vue'
import { updateUserRole, removeUser } from '@/services/usersService'
import { addUserToGroup, removeUserFromGroup } from '@/services/groupsService'
import { useToast } from '@/composables/useToast'
import type { User, UserRole } from '@/types/users'

const { showToast } = useToast()
import groupsDataJson from '@/assets/mock-data/groups-data.json'

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
const selectedRole = ref<UserRole>(props.user?.role || 'Viewer')
const userGroups = ref<string[]>(props.user ? [...props.user.groups] : [])

const allGroups = computed(() => groupsDataJson.data)

const availableGroups = computed(() => {
  return allGroups.value.filter(g => !userGroups.value.includes(g.id))
})

watch(() => props.user, (newUser) => {
  if (newUser) {
    selectedRole.value = newUser.role
    userGroups.value = [...newUser.groups]
  }
}, { immediate: true })

watch(selectedRole, async (newRole) => {
  if (props.user && newRole !== props.user.role) {
    try {
      await updateUserRole(props.user.id, newRole, userGroups.value)
      emit('user-updated')
      showToast('User role updated successfully', 'success')
    } catch (error) {
      console.error('Failed to update user role:', error)
      showToast('Failed to update user role', 'error')
      selectedRole.value = props.user.role
    }
  }
})

const handleAddGroup = async (groupId: string) => {
  if (!props.user) return
  try {
    await addUserToGroup(groupId, props.user.id)
    userGroups.value.push(groupId)
    await updateUserRole(props.user.id, selectedRole.value, userGroups.value)
    addGroupDropdownOpen.value = false
    emit('user-updated')
    showToast('User added to group', 'success')
  } catch (error) {
    console.error('Failed to add user to group:', error)
    showToast('Failed to add user to group', 'error')
  }
}

const handleRemoveGroup = async (groupId: string) => {
  if (!props.user) return
  try {
    await removeUserFromGroup(groupId, props.user.id)
    userGroups.value = userGroups.value.filter(id => id !== groupId)
    await updateUserRole(props.user.id, selectedRole.value, userGroups.value)
    emit('user-updated')
    showToast('User removed from group', 'success')
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

const getGroupName = (groupId: string) => {
  const group = allGroups.value.find(g => g.id === groupId)
  return group?.name || groupId
}
</script>

<template>
  <Drawer
    :model-value="modelValue"
    id="user-detail-drawer"
    label="USER DETAILS"
    width="w-96"
    placement="right"
    @update:model-value="emit('update:modelValue', $event)"
  >
    <div v-if="user" class="space-y-6">
      <!-- User Information -->
      <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg">
        <div class="py-3 mb-4 border-b border-gray-200 dark:border-gray-700">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white">User Information</h3>
        </div>
        <div class="space-y-4">
          <dl class="flex items-center justify-between gap-4">
            <dt class="text-sm font-normal text-gray-500 dark:text-gray-400">Name</dt>
            <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ user.name }}</dd>
          </dl>
          <dl class="flex items-center justify-between gap-4">
            <dt class="text-sm font-normal text-gray-500 dark:text-gray-400">Email</dt>
            <dd class="text-sm font-medium text-green-600">{{ user.email }}</dd>
          </dl>
          <dl class="flex items-center justify-between gap-4">
            <dt class="text-sm font-normal text-gray-500 dark:text-gray-400">Status</dt>
            <dd class="text-sm font-medium text-gray-900 dark:text-white">
              <div class="flex items-center">
                <div
                  :class="[
                    'mr-2 h-2 w-2 rounded-full',
                    user.status === 'active' ? 'bg-green-500' : 'bg-gray-500'
                  ]"
                ></div>
                {{ user.status === 'active' ? 'Active' : 'Inactive' }}
              </div>
            </dd>
          </dl>
          <dl class="flex items-center justify-between gap-4">
            <dt class="text-sm font-normal text-gray-500 dark:text-gray-400">Role</dt>
            <dd class="w-1/2">
              <select
                v-model="selectedRole"
                class="block w-full py-1 px-2 text-sm text-gray-900 border border-gray-300 rounded-lg bg-gray-50 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
              >
                <option value="Admin">Admin</option>
                <option value="Developer">Developer</option>
                <option value="Viewer">Viewer</option>
              </select>
            </dd>
          </dl>
        </div>
      </div>

      <!-- Groups -->
      <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg">
        <div class="py-3 mb-4 border-b border-gray-200 dark:border-gray-700">
          <div class="flex items-center justify-between gap-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Groups</h3>
            <div v-if="availableGroups.length > 0" class="relative">
              <button 
                @click="addGroupDropdownOpen = !addGroupDropdownOpen"
                class="flex shrink-0 items-center text-sm font-medium text-primary-700 hover:underline dark:text-primary-500"
              >
                <svg class="me-1 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                  <path fill-rule="evenodd" d="M2 12a10 10 0 1 1 20 0 10 10 0 0 1-20 0Zm11-4.2a1 1 0 1 0-2 0V11H7.8a1 1 0 1 0 0 2H11v3.2a1 1 0 1 0 2 0V13h3.2a1 1 0 1 0 0-2H13V7.8Z" clip-rule="evenodd"></path>
                </svg>
                Add group
              </button>
              <div 
                v-show="addGroupDropdownOpen"
                class="absolute right-0 top-full mt-1 w-48 bg-white dark:bg-gray-700 rounded-lg shadow-lg border border-gray-200 dark:border-gray-600 z-10"
              >
                <ul class="p-2">
                  <li
                    v-for="group in availableGroups"
                    :key="group.id"
                    @click="handleAddGroup(group.id)"
                    class="px-3 py-2 text-sm text-gray-900 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600 rounded cursor-pointer"
                  >
                    {{ group.name }}
                  </li>
                </ul>
              </div>
            </div>
          </div>
        </div>
        <div class="space-y-1">
          <div
            v-for="groupId in userGroups"
            :key="groupId"
            class="flex items-center justify-between gap-4 py-1 hover:bg-gray-100 dark:hover:bg-gray-700"
          >
            <span class="text-sm text-gray-900 dark:text-gray-300">{{ getGroupName(groupId) }}</span>
            <button
              type="button"
              @click="handleRemoveGroup(groupId)"
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

  <!-- Confirm Remove Modal -->
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
      Are you sure you want to remove <strong>{{ user.name }}</strong> from the system? This action cannot be undone.
    </p>
  </Confirm>
</template>

