<script setup lang="ts">
import { computed } from 'vue';
import type { ApplicationWithStatus, ApplicationStatus } from '@/types/applications';
import Button from '@/components/ui/Button.vue';

interface Props {
  application: ApplicationWithStatus;
}

const props = defineProps<Props>();

const emit = defineEmits<{
  openDetail: [key: string, worker: string, status: ApplicationStatus];
  install: [key: string, worker: string];
}>();

const statusLabel = computed(() => {
  switch (props.application.status) {
    case 'activated':
      return 'Activated';
    case 'authorized':
      return 'Authorized';
    case 'installed':
      return 'Unauthorized';
    case 'available':
    default:
      return ''; // No badge for available apps
  }
});

const showStatusBadge = computed(() => {
  // Only show badge if not available
  return props.application.status !== 'available';
});

const statusBadgeClass = computed(() => {
  switch (props.application.status) {
    case 'activated':
      return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300';
    case 'authorized':
      return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300';
    case 'installed':
      return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300';
    case 'available':
    default:
      return '';
  }
});

const isAvailable = computed(() => {
  return props.application.status === 'available';
});

const handleOpenDetail = () => {
  emit('openDetail', props.application.key, props.application.worker || '', props.application.status);
};

const handleInstall = () => {
  emit('install', props.application.key, props.application.worker || '');
};
</script>

<template>
  <div class="space-y-4 rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800" :data-status="application.status">
    <div class="flex items-center justify-between h-7">
      <!-- Logo & Name -->
      <div class="flex items-center">
        <!-- Display API logo or fallback to generic icon -->
        <img
          v-if="application.logo"
          :src="application.logo"
          :alt="`${application.name} logo`"
          class="me-2 h-7 w-7"
        />
        <svg
          v-else
          xmlns="http://www.w3.org/2000/svg"
          class="me-2 h-7 text-gray-900 dark:text-white"
          height="24px"
          viewBox="0 -960 960 960"
          width="24px"
          fill="currentColor"
        >
          <path
            d="M240-160q-33 0-56.5-23.5T160-240q0-33 23.5-56.5T240-320q33 0 56.5 23.5T320-240q0 33-23.5 56.5T240-160Zm240 0q-33 0-56.5-23.5T400-240q0-33 23.5-56.5T480-320q33 0 56.5 23.5T560-240q0 33-23.5 56.5T480-160Zm240 0q-33 0-56.5-23.5T640-240q0-33 23.5-56.5T720-320q33 0 56.5 23.5T800-240q0 33-23.5 56.5T720-160ZM240-400q-33 0-56.5-23.5T160-480q0-33 23.5-56.5T240-560q33 0 56.5 23.5T320-480q0 33-23.5 56.5T240-400Zm240 0q-33 0-56.5-23.5T400-480q0-33 23.5-56.5T480-560q33 0 56.5 23.5T560-480q0 33-23.5 56.5T480-400Zm240 0q-33 0-56.5-23.5T640-480q0-33 23.5-56.5T720-560q33 0 56.5 23.5T800-480q0 33-23.5 56.5T720-400ZM240-640q-33 0-56.5-23.5T160-720q0-33 23.5-56.5T240-800q33 0 56.5 23.5T320-720q0 33-23.5 56.5T240-640Zm240 0q-33 0-56.5-23.5T400-720q0-33 23.5-56.5T480-800q33 0 56.5 23.5T560-720q0 33-23.5 56.5T480-640Zm240 0q-33 0-56.5-23.5T640-720q0-33 23.5-56.5T720-800q33 0 56.5 23.5T800-720q0 33-23.5 56.5T720-640Z"
          />
        </svg>
        <span class="font-semibold text-gray-900 dark:text-white">{{ application.name }}</span>
      </div>

      <!-- Status Label - only show if not available -->
      <span
        v-if="showStatusBadge"
        class="text-xs font-medium px-2.5 py-0.5 rounded"
        :class="statusBadgeClass"
      >
        {{ statusLabel }}
      </span>
    </div>

    <div class="min-h-[3rem]">
      <p class="text-gray-500 dark:text-gray-400">{{ application.description }}</p>
    </div>

    <!-- Action Buttons -->
    <div class="flex items-center gap-2">
      <Button
        v-if="isAvailable"
        variant="primary"
        class="flex-1"
        @click="handleInstall"
      >
        Install
      </Button>
      <Button
        variant="outline"
        class="flex-1"
        @click="handleOpenDetail"
      >
        Open
      </Button>
    </div>
  </div>
</template>

