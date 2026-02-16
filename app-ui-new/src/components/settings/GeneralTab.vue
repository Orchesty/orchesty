<script setup lang="ts">
import { useDateFormat, type DateFormatType } from '@/composables/useDateFormat'
import { useToast } from '@/composables/useToast'

const { dateFormat, setDateFormat, formatDateTime } = useDateFormat()
const { showToast } = useToast()

const handleFormatChange = (format: DateFormatType) => {
  setDateFormat(format)
  showToast('Date format updated', 'success')
}

const previewDate = new Date('2026-02-09T14:30:05')
</script>

<template>
  <div class="space-y-6">
    <!-- Section: Date & Time Format -->
    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
      <h3 class="mb-1 text-lg font-semibold text-gray-900 dark:text-white">Date & Time Format</h3>
      <p class="mb-5 text-sm text-gray-500 dark:text-gray-400">
        Choose how dates and times are displayed throughout the application.
      </p>

      <div class="space-y-3">
        <!-- European -->
        <label
          class="flex cursor-pointer items-center rounded-lg border p-4 transition-colors"
          :class="dateFormat === 'eu'
            ? 'border-primary-500 bg-primary-50 dark:border-primary-400 dark:bg-primary-900/20'
            : 'border-gray-200 hover:bg-gray-50 dark:border-gray-600 dark:hover:bg-gray-700/50'"
        >
          <input
            type="radio"
            name="date-format"
            value="eu"
            :checked="dateFormat === 'eu'"
            class="h-4 w-4 border-gray-300 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
            @change="handleFormatChange('eu')"
          >
          <div class="ms-3">
            <span class="text-sm font-medium text-gray-900 dark:text-white">European</span>
            <span class="ms-2 text-sm text-gray-500 dark:text-gray-400">DD.MM.YYYY, 24h</span>
          </div>
        </label>

        <!-- American -->
        <label
          class="flex cursor-pointer items-center rounded-lg border p-4 transition-colors"
          :class="dateFormat === 'us'
            ? 'border-primary-500 bg-primary-50 dark:border-primary-400 dark:bg-primary-900/20'
            : 'border-gray-200 hover:bg-gray-50 dark:border-gray-600 dark:hover:bg-gray-700/50'"
        >
          <input
            type="radio"
            name="date-format"
            value="us"
            :checked="dateFormat === 'us'"
            class="h-4 w-4 border-gray-300 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
            @change="handleFormatChange('us')"
          >
          <div class="ms-3">
            <span class="text-sm font-medium text-gray-900 dark:text-white">American</span>
            <span class="ms-2 text-sm text-gray-500 dark:text-gray-400">MM/DD/YYYY, 12h AM/PM</span>
          </div>
        </label>
      </div>

      <!-- Preview -->
      <div class="mt-5 rounded-lg bg-gray-50 p-4 dark:bg-gray-700/50">
        <p class="mb-1 text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Preview</p>
        <p class="text-sm font-medium text-gray-900 dark:text-white">
          {{ formatDateTime(previewDate) }}
        </p>
      </div>
    </div>
  </div>
</template>
