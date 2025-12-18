<script setup lang="ts">
import { ref, watch } from 'vue'
import Drawer from '@/components/ui/Drawer.vue'
import Button from '@/components/ui/Button.vue'
import CopyValue from '@/components/ui/CopyValue.vue'
import LoadingSpinner from '@/components/ui/LoadingSpinner.vue'
import type { Process, ProcessAuditDetail } from '@/types/processes'

interface Props {
  modelValue: boolean
  process: Process | null
}

const props = defineProps<Props>()

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
}>()

// Data state
const processDetail = ref<ProcessAuditDetail | null>(null)
const loading = ref(false)

// Debug
watch(() => props.modelValue, (newVal) => {
  console.log('ProcessAuditDrawer modelValue changed:', newVal)
})

watch(() => props.process, (newVal) => {
  console.log('ProcessAuditDrawer process changed:', newVal)
})

// Load process audit detail when process changes
watch(
  () => props.process,
  async (newProcess) => {
    if (!newProcess) {
      processDetail.value = null
      return
    }

    loading.value = true
    try {
      // TODO: Replace with actual API call
      // Simulate API delay
      await new Promise((resolve) => setTimeout(resolve, 300))

      // Mock data - will be replaced with actual API call
      processDetail.value = {
        processId: newProcess.id,
        topology: newProcess.topology,
        corelId: 'a8f3b2c9e7d4',
        startTime: newProcess.startTime,
        endTime: calculateEndTime(newProcess.startTime, newProcess.duration),
        status: newProcess.status,
        connectors: [
          {
            connector: 'Lead Sync',
            application: 'Salesforce',
            called: 42,
            errors400: 3,
            errors500: 1,
          },
          {
            connector: 'Contact Import',
            application: 'HubSpot',
            called: 18,
            errors400: 1,
            errors500: 0,
          },
          {
            connector: 'Message Sync',
            application: 'Slack',
            called: 25,
            errors400: 0,
            errors500: 0,
          },
        ],
        trashCount: 5,
        trashItems: [
          {
            whereItFailed: 'Lead Sync Action',
            errorMessage: "Invalid request format: missing required field 'email'",
          },
          {
            whereItFailed: 'Contact Import Connector',
            errorMessage: 'Authentication failed: token expired',
          },
          {
            whereItFailed: 'Message Sync Action',
            errorMessage: 'Database connection timeout after 30 seconds',
          },
          {
            whereItFailed: 'Lead Update Action',
            errorMessage: 'Rate limit exceeded: too many requests',
          },
          {
            whereItFailed: 'Email Sync Connector',
            errorMessage: 'Invalid JSON response: malformed data structure',
          },
        ],
      }
    } catch (error) {
      console.error('Error loading process audit:', error)
    } finally {
      loading.value = false
    }
  },
  { immediate: true }
)

const calculateEndTime = (startTime: string, durationSeconds: number): string => {
  const start = new Date(startTime)
  const end = new Date(start.getTime() + durationSeconds * 1000)
  return end.toISOString().replace('T', ' ').substring(0, 19)
}

const handleClose = () => {
  emit('update:modelValue', false)
}
</script>

<template>
  <Drawer
    :model-value="modelValue"
    id="process-audit-drawer"
    label="Process Audit"
    width="w-1/2 min-w-[600px]"
    @update:model-value="handleClose"
  >
    <LoadingSpinner v-if="loading" message="Loading process details..." />

    <div v-else-if="processDetail" class="space-y-6">
      <!-- Topology Header -->
      <div class="mb-6 border-b border-gray-200 pb-6 dark:border-gray-700">
        <div class="mb-3 flex items-start justify-between">
          <div>
            <h2 class="mb-3 text-2xl font-bold text-gray-900 dark:text-white">
              {{ processDetail.topology }}
            </h2>
            <div class="flex items-center gap-2">
              <span class="text-sm text-gray-500 dark:text-gray-400">Corel ID:</span>
              <CopyValue :value="processDetail.corelId">
                <span class="font-mono text-sm text-gray-900 dark:text-white">{{
                  processDetail.corelId
                }}</span>
              </CopyValue>
            </div>
          </div>
          <div class="flex items-center gap-2">
            <button
              type="button"
              class="inline-flex items-center justify-center rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
            >
              <svg
                class="-ms-0.5 me-1.5 h-4 w-4"
                aria-hidden="true"
                xmlns="http://www.w3.org/2000/svg"
                fill="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  fill-rule="evenodd"
                  d="M9 2.221V7H4.221a2 2 0 0 1 .365-.5L8.5 2.586A2 2 0 0 1 9 2.22ZM11 2v5a2 2 0 0 1-2 2H4a2 2 0 0 0-2 2v7a2 2 0 0 0 2 2 2 2 0 0 0 2 2h12a2 2 0 0 0 2-2 2 2 0 0 0 2-2v-7a2 2 0 0 0-2-2V4a2 2 0 0 0-2-2h-7Zm-6 9a1 1 0 0 0-1 1v5a1 1 0 1 0 2 0v-1h.5a2.5 2.5 0 0 0 0-5H5Zm1.5 3H6v-1h.5a.5.5 0 0 1 0 1Zm4.5-3a1 1 0 0 0-1 1v5a1 1 0 0 0 1 1h1.376A2.626 2.626 0 0 0 15 15.375v-1.75A2.626 2.626 0 0 0 12.375 11H11Zm1 5v-3h.375a.626.626 0 0 1 .625.626v1.748a.625.625 0 0 1-.626.626H12Zm5-5a1 1 0 0 0-1 1v5a1 1 0 1 0 2 0v-1h1a1 1 0 1 0 0-2h-1v-1h1a1 1 0 1 0 0-2h-2Z"
                  clip-rule="evenodd"
                />
              </svg>
              Export PDF
            </button>
            <button
              type="button"
              class="inline-flex items-center justify-center rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
            >
              <svg
                class="me-1.5 h-4 w-4"
                aria-hidden="true"
                xmlns="http://www.w3.org/2000/svg"
                width="24"
                height="24"
                fill="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  fill-rule="evenodd"
                  d="M13 11.15V4a1 1 0 1 0-2 0v7.15L8.78 8.374a1 1 0 1 0-1.56 1.25l4 5a1 1 0 0 0 1.56 0l4-5a1 1 0 1 0-1.56-1.25L13 11.15Z"
                  clip-rule="evenodd"
                />
                <path
                  fill-rule="evenodd"
                  d="M9.657 15.874 7.358 13H5a2 2 0 0 0-2 2v4a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-4a2 2 0 0 0-2-2h-2.358l-2.3 2.874a3 3 0 0 1-4.685 0ZM17 16a1 1 0 1 0 0 2h.01a1 1 0 1 0 0-2H17Z"
                  clip-rule="evenodd"
                />
              </svg>
              Get Payload
            </button>
          </div>
        </div>
      </div>

      <!-- Process Info -->
      <div class="grid grid-cols-3 gap-4">
        <div>
          <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300"
            >Start</label
          >
          <p class="text-sm text-gray-900 dark:text-white">{{ processDetail.startTime }}</p>
        </div>
        <div>
          <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">End</label>
          <p class="text-sm text-gray-900 dark:text-white">{{ processDetail.endTime }}</p>
        </div>
        <div>
          <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300"
            >Status</label
          >
          <span
            :class="[
              'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
              processDetail.status === 'completed'
                ? 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-300'
                : processDetail.status === 'running'
                ? 'bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-300'
                : 'bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-300',
            ]"
          >
            {{ processDetail.status.charAt(0).toUpperCase() + processDetail.status.slice(1) }}
          </span>
        </div>
      </div>

      <!-- Connectors Table -->
      <div>
        <h4 class="mb-3 text-sm font-medium text-gray-900 dark:text-white">Connectors</h4>
        <div class="overflow-x-auto">
          <table class="w-full text-left text-sm text-gray-500 dark:text-gray-400">
            <thead
              class="bg-gray-50 text-xs uppercase text-gray-700 dark:bg-gray-700 dark:text-gray-400"
            >
              <tr>
                <th scope="col" class="px-4 py-3">Connector / Application</th>
                <th scope="col" class="px-4 py-3">Voláno</th>
                <th scope="col" class="px-4 py-3">400</th>
                <th scope="col" class="px-4 py-3">500</th>
              </tr>
            </thead>
            <tbody class="divide-y bg-white dark:divide-gray-700 dark:bg-gray-800">
              <tr v-for="connector in processDetail.connectors" :key="connector.connector">
                <td class="px-4 py-3">
                  <span class="font-medium text-gray-900 dark:text-white">{{
                    connector.connector
                  }}</span>
                  <span class="text-gray-500 dark:text-gray-400"> ({{ connector.application }})</span>
                </td>
                <td class="px-4 py-3">{{ connector.called }}</td>
                <td class="px-4 py-3">
                  <span
                    v-if="connector.errors400 > 0"
                    class="inline-flex items-center rounded-full bg-yellow-100 px-2 py-1 text-xs font-medium text-yellow-700 dark:bg-yellow-800 dark:text-yellow-300"
                    >{{ connector.errors400 }}</span
                  >
                  <span v-else>-</span>
                </td>
                <td class="px-4 py-3">
                  <span
                    v-if="connector.errors500 > 0"
                    class="inline-flex items-center rounded-full bg-red-100 px-2 py-1 text-xs font-medium text-red-700 dark:bg-red-800 dark:text-red-300"
                    >{{ connector.errors500 }}</span
                  >
                  <span v-else>-</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Trash Status -->
      <div>
        <h4 class="mb-3 text-sm font-medium text-gray-900 dark:text-white">Trash Status</h4>
        <div class="flex items-center gap-3">
          <div
            class="inline-flex items-center rounded bg-red-100 px-3 py-1.5 text-sm font-medium text-red-800 dark:bg-red-800 dark:text-red-300"
          >
            {{ processDetail.trashCount }} messages in trash
          </div>
          <a
            href="#"
            class="flex shrink-0 items-center text-sm font-medium text-primary-700 hover:underline dark:text-primary-500"
          >
            Go to trash
          </a>
        </div>
      </div>

      <!-- Trash Table -->
      <div>
        <h4 class="mb-3 text-sm font-medium text-gray-900 dark:text-white">Trash Details</h4>
        <div class="overflow-x-auto">
          <table class="w-full text-left text-sm text-gray-500 dark:text-gray-400">
            <thead
              class="bg-gray-50 text-xs uppercase text-gray-700 dark:bg-gray-700 dark:text-gray-400"
            >
              <tr>
                <th scope="col" class="px-4 py-3">Where it failed</th>
                <th scope="col" class="px-4 py-3">Error message</th>
              </tr>
            </thead>
            <tbody class="divide-y bg-white dark:divide-gray-700 dark:bg-gray-800">
              <tr v-for="(item, index) in processDetail.trashItems" :key="index">
                <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">
                  {{ item.whereItFailed }}
                </td>
                <td class="px-4 py-3">{{ item.errorMessage }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Footer -->
    <template #footer-actions>
      <Button variant="outline" @click="handleClose">
        Close
      </Button>
    </template>
  </Drawer>
</template>

