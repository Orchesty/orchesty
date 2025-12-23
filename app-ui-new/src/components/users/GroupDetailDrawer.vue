<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import Drawer from '@/components/ui/Drawer.vue'
import Button from '@/components/ui/Button.vue'
import Confirm from '@/components/ui/Confirm.vue'
import { updateGroup, removeGroup, addUserToGroup, removeUserFromGroup } from '@/services/groupsService'
import type { Group } from '@/types/users'
import usersDataJson from '@/assets/mock-data/users-data.json'

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
const addUserDropdownOpen = ref(false)
const groupModules = ref<string[]>(props.group ? [...props.group.modules] : [])
const groupUsers = ref<string[]>(props.group ? [...props.group.users] : [])

const allModules = [
  { id: 'dashboard', label: 'Dashboard' },
  { id: 'ai-assistant', label: 'AI Assistant' },
  { id: 'integrations', label: 'Integrations' },
  { id: 'analytics', label: 'Analytics' },
  { id: 'settings', label: 'Settings' }
]

const allUsers = computed(() => usersDataJson.data)

const availableUsers = computed(() => {
  return allUsers.value.filter(u => !groupUsers.value.includes(u.id))
})

watch(() => props.group, (newGroup) => {
  if (newGroup) {
    groupModules.value = [...newGroup.modules]
    groupUsers.value = [...newGroup.users]
  }
}, { immediate: true })

const handleModuleChange = async (moduleId: string, checked: boolean) => {
  if (!props.group) return
  
  if (checked) {
    groupModules.value.push(moduleId)
  } else {
    groupModules.value = groupModules.value.filter(id => id !== moduleId)
  }

  try {
    await updateGroup(props.group.id, { modules: groupModules.value })
    emit('group-updated')
  } catch (error) {
    console.error('Failed to update group modules:', error)
  }
}

const handleAddUser = async (userId: string) => {
  if (!props.group) return
  try {
    await addUserToGroup(props.group.id, userId)
    groupUsers.value.push(userId)
    await updateGroup(props.group.id, { users: groupUsers.value })
    addUserDropdownOpen.value = false
    emit('group-updated')
  } catch (error) {
    console.error('Failed to add user to group:', error)
  }
}

const handleRemoveUser = async (userId: string) => {
  if (!props.group) return
  try {
    await removeUserFromGroup(props.group.id, userId)
    groupUsers.value = groupUsers.value.filter(id => id !== userId)
    await updateGroup(props.group.id, { users: groupUsers.value })
    emit('group-updated')
  } catch (error) {
    console.error('Failed to remove user from group:', error)
  }
}

const handleConfirmRemove = async () => {
  if (!props.group) return
  try {
    await removeGroup(props.group.id)
    confirmRemoveOpen.value = false
    emit('group-removed')
  } catch (error) {
    console.error('Failed to remove group:', error)
  }
}

const getUserName = (userId: string) => {
  const user = allUsers.value.find(u => u.id === userId)
  return user?.name || userId
}

const isModuleChecked = (moduleId: string) => {
  return groupModules.value.includes(moduleId)
}
</script>

<template>
  <Drawer
    :model-value="modelValue"
    id="group-detail-drawer"
    label="GROUP DETAILS"
    width="w-96"
    placement="right"
    @update:model-value="emit('update:modelValue', $event)"
  >
    <div v-if="group" class="space-y-6">
      <!-- Group Information -->
      <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg">
        <div class="py-3 mb-4 border-b border-gray-200 dark:border-gray-700">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Group Information</h3>
        </div>
        <div class="space-y-4">
          <dl class="flex items-center justify-between gap-4">
            <dt class="text-sm font-normal text-gray-500 dark:text-gray-400">Name</dt>
            <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ group.name }}</dd>
          </dl>
          <dl class="flex items-center justify-between gap-4">
            <dt class="text-sm font-normal text-gray-500 dark:text-gray-400">Modules</dt>
            <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ groupModules.length }}</dd>
          </dl>
          <dl class="flex items-center justify-between gap-4">
            <dt class="text-sm font-normal text-gray-500 dark:text-gray-400">Users</dt>
            <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ groupUsers.length }}</dd>
          </dl>
        </div>
      </div>

      <!-- Modules -->
      <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg">
        <div class="py-3 mb-4 border-b border-gray-200 dark:border-gray-700">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Modules</h3>
        </div>
        <div class="space-y-4">
          <div v-for="module in allModules" :key="module.id" class="flex items-center">
            <input
              :id="`module-${module.id}`"
              type="checkbox"
              :checked="isModuleChecked(module.id)"
              @change="handleModuleChange(module.id, ($event.target as HTMLInputElement).checked)"
              class="w-3 h-3 text-blue-600 bg-gray-100 border-gray-300 rounded-sm focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
            >
            <label :for="`module-${module.id}`" class="ms-2 text-sm text-gray-900 dark:text-gray-300">
              {{ module.label }}
            </label>
          </div>
        </div>
      </div>

      <!-- Users -->
      <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg">
        <div class="py-3 mb-4 border-b border-gray-200 dark:border-gray-700">
          <div class="flex items-center justify-between gap-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Users</h3>
            <div v-if="availableUsers.length > 0" class="relative">
              <button 
                @click="addUserDropdownOpen = !addUserDropdownOpen"
                class="flex shrink-0 items-center text-sm font-medium text-primary-700 hover:underline dark:text-primary-500"
              >
                <svg class="me-1 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                  <path fill-rule="evenodd" d="M2 12a10 10 0 1 1 20 0 10 10 0 0 1-20 0Zm11-4.2a1 1 0 1 0-2 0V11H7.8a1 1 0 1 0 0 2H11v3.2a1 1 0 1 0 2 0V13h3.2a1 1 0 1 0 0-2H13V7.8Z" clip-rule="evenodd"></path>
                </svg>
                Add user
              </button>
              <div 
                v-show="addUserDropdownOpen"
                class="absolute right-0 top-full mt-1 w-48 bg-white dark:bg-gray-700 rounded-lg shadow-lg border border-gray-200 dark:border-gray-600 z-10 max-h-48 overflow-y-auto"
              >
                <ul class="p-2">
                  <li
                    v-for="user in availableUsers"
                    :key="user.id"
                    @click="handleAddUser(user.id)"
                    class="px-3 py-2 text-sm text-gray-900 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600 rounded cursor-pointer"
                  >
                    {{ user.name }}
                  </li>
                </ul>
              </div>
            </div>
          </div>
        </div>
        <div class="space-y-1">
          <div
            v-for="userId in groupUsers"
            :key="userId"
            class="flex items-center justify-between gap-4 py-1 hover:bg-gray-100 dark:hover:bg-gray-700"
          >
            <span class="text-sm text-gray-900 dark:text-gray-300">{{ getUserName(userId) }}</span>
            <button
              type="button"
              @click="handleRemoveUser(userId)"
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
    v-if="group"
    v-model="confirmRemoveOpen"
    id="confirm-remove-group-modal"
    confirm-text="Yes, remove"
    cancel-text="Cancel"
    @confirm="handleConfirmRemove"
    @cancel="confirmRemoveOpen = false"
  >
    <p class="text-sm text-gray-700 dark:text-gray-300">
      Are you sure you want to remove <strong>{{ group.name }}</strong>? This action cannot be undone.
    </p>
  </Confirm>
</template>

