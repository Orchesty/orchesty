<script setup lang="ts">
import { ref, computed, onMounted, nextTick, watch } from 'vue'
import Drawer from '@/components/ui/Drawer.vue'
import type { TraceReport } from '@/types/trace'

interface Props {
  modelValue: boolean
  reports: TraceReport[]
}

const props = defineProps<Props>()

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  'open-report': [report: TraceReport]
  'rename': [id: string, newTitle: string]
  'delete': [id: string]
}>()

const editingId = ref<string | null>(null)
const editingTitle = ref('')

// Seskupit reporty podle data
const reportsByDate = computed(() => {
  const grouped: Record<string, TraceReport[]> = {}
  
  props.reports.forEach(report => {
    const date = new Date(report.timestamp)
    const dateKey = date.toISOString().split('T')[0]
    
    if (!grouped[dateKey]) {
      grouped[dateKey] = []
    }
    grouped[dateKey].push(report)
  })
  
  // Seřadit podle data (nejnovější první)
  return Object.entries(grouped)
    .sort(([dateA], [dateB]) => dateB.localeCompare(dateA))
    .map(([date, reports]) => ({
      date,
      dateFormatted: formatDate(date),
      reports: reports.sort((a, b) => 
        new Date(b.timestamp).getTime() - new Date(a.timestamp).getTime()
      )
    }))
})

const formatDate = (dateStr: string) => {
  const date = new Date(dateStr)
  const today = new Date()
  const yesterday = new Date(today)
  yesterday.setDate(yesterday.getDate() - 1)
  
  if (dateStr === today.toISOString().split('T')[0]) {
    return 'Today'
  } else if (dateStr === yesterday.toISOString().split('T')[0]) {
    return 'Yesterday'
  } else {
    return date.toLocaleDateString('en-US', { 
      month: 'long', 
      day: 'numeric', 
      year: 'numeric' 
    })
  }
}

const formatTime = (timestamp: Date) => {
  return new Date(timestamp).toLocaleTimeString('en-US', {
    hour: '2-digit',
    minute: '2-digit'
  })
}

const handleOpenReport = (report: TraceReport) => {
  if (editingId.value === report.id) return
  emit('open-report', report)
}

const startRename = (report: TraceReport) => {
  editingId.value = report.id
  editingTitle.value = report.title
}

const saveRename = (reportId: string) => {
  if (editingTitle.value.trim() && editingTitle.value !== editingTitle.value) {
    emit('rename', reportId, editingTitle.value.trim())
  }
  editingId.value = null
  editingTitle.value = ''
}

const cancelRename = () => {
  editingId.value = null
  editingTitle.value = ''
}

const handleDelete = (reportId: string) => {
  emit('delete', reportId)
}

const handleKeydown = (event: KeyboardEvent, reportId: string) => {
  if (event.key === 'Enter') {
    event.preventDefault()
    saveRename(reportId)
  } else if (event.key === 'Escape') {
    cancelRename()
  }
}

// Initialize Flowbite dropdowns only within the drawer
const initDropdowns = async () => {
  await nextTick()
  const drawerEl = document.getElementById('trace-reports-drawer')
  if (!drawerEl) return
  
  // Initialize only dropdown elements within this drawer
  const dropdownTriggers = drawerEl.querySelectorAll('[data-dropdown-toggle]')
  if (dropdownTriggers.length === 0) return
  
  const { Dropdown } = await import('flowbite')
  
  dropdownTriggers.forEach((trigger) => {
    const targetId = trigger.getAttribute('data-dropdown-toggle')
    if (!targetId) return
    
    const targetEl = document.getElementById(targetId)
    if (!targetEl) return
    
    // Create dropdown instance for this specific trigger
    new Dropdown(targetEl, trigger as HTMLElement, {
      placement: 'bottom',
      triggerType: 'click',
    })
  })
}

// Watch for drawer open and reports changes
watch(() => props.modelValue, (isOpen) => {
  if (isOpen) {
    initDropdowns()
  }
})

watch(() => props.reports, () => {
  if (props.modelValue) {
    initDropdowns()
  }
}, { deep: true })

onMounted(() => {
  if (props.modelValue) {
    initDropdowns()
  }
})
</script>

<template>
  <Drawer
    id="trace-reports-drawer"
    :model-value="modelValue"
    @update:model-value="$emit('update:modelValue', $event)"
    label="Trace Reports"
    width="w-96"
    placement="right"
  >
    <div class="space-y-6">
      <!-- Reports List -->
      <div v-if="reportsByDate.length > 0" class="space-y-6">
        <div v-for="group in reportsByDate" :key="group.date" class="space-y-3">
          <!-- Date Header -->
          <h3 class="text-sm font-semibold text-gray-900 dark:text-white">
            {{ group.dateFormatted }}
          </h3>
          
          <!-- Reports in this date -->
          <div class="space-y-2">
            <div
              v-for="report in group.reports"
              :key="report.id"
              class="group/item relative rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
            >
              <!-- Report Item -->
              <div class="flex items-start gap-3 p-3">
                <div class="flex-1 min-w-0">
                  <!-- Title (editable) -->
                  <div v-if="editingId === report.id" class="mb-1">
                    <input
                      v-model="editingTitle"
                      type="text"
                      @keydown="handleKeydown($event, report.id)"
                      @blur="saveRename(report.id)"
                      class="w-full px-2 py-1 text-sm font-medium text-gray-900 dark:text-white bg-white dark:bg-gray-800 border border-primary-600 rounded focus:outline-none focus:ring-2 focus:ring-primary-500"
                      autofocus
                    />
                  </div>
                  <div v-else>
                    <button
                      @click="handleOpenReport(report)"
                      class="text-left text-sm font-medium text-gray-900 dark:text-white hover:text-primary-600 dark:hover:text-primary-500 line-clamp-2 mb-1"
                    >
                      {{ report.title }}
                    </button>
                  </div>
                  
                  <!-- Time -->
                  <p class="text-xs text-gray-500 dark:text-gray-400">
                    {{ formatTime(report.timestamp) }}
                  </p>
                </div>
                
                <!-- Dropdown Menu -->
                <div class="relative">
                  <button
                    :id="`report-menu-${report.id}`"
                    :data-dropdown-toggle="`report-dropdown-${report.id}`"
                    class="inline-flex items-center p-1.5 text-sm font-medium text-center text-gray-500 hover:text-gray-800 hover:bg-gray-200 dark:hover:bg-gray-600 dark:text-gray-400 dark:hover:text-gray-100 rounded-lg focus:outline-none opacity-0 group-hover/item:opacity-100 transition-opacity"
                    type="button"
                  >
                    <svg class="w-4 h-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 4 15">
                      <path d="M3.5 1.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Zm0 6.041a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Zm0 5.959a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Z"/>
                    </svg>
                  </button>
                  
                  <!-- Dropdown menu -->
                  <div
                    :id="`report-dropdown-${report.id}`"
                    class="z-10 hidden bg-white divide-y divide-gray-100 rounded-lg shadow w-40 dark:bg-gray-700 dark:divide-gray-600"
                  >
                    <ul class="py-1 text-sm text-gray-700 dark:text-gray-200">
                      <li>
                        <button
                          @click="startRename(report)"
                          class="w-full text-left block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white"
                        >
                          Rename
                        </button>
                      </li>
                      <li>
                        <button
                          @click="handleDelete(report.id)"
                          class="w-full text-left block px-4 py-2 text-red-600 hover:bg-gray-100 dark:hover:bg-gray-600 dark:text-red-500 dark:hover:text-red-400"
                        >
                          Delete
                        </button>
                      </li>
                    </ul>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Empty State -->
      <div v-else class="text-center py-12">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No reports</h3>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
          Save a message to create your first report
        </p>
      </div>
    </div>
  </Drawer>
</template>

