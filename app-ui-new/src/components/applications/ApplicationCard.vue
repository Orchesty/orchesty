<script setup lang="ts">
import { computed } from 'vue';
import type { ApplicationWithStatus } from '@/types/applications';
import Button from '@/components/ui/Button.vue';

interface Props {
  application: ApplicationWithStatus;
}

const props = defineProps<Props>();

const emit = defineEmits<{
  openDetail: [key: string];
}>();

const statusBadgeClass = computed(() => {
  switch (props.application.status) {
    case 'authorized':
      return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300';
    case 'unauthorized':
      return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300';
    case 'uninstalled':
    default:
      return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
  }
});

const statusLabel = computed(() => {
  switch (props.application.status) {
    case 'authorized':
      return 'Authorized';
    case 'unauthorized':
      return 'Unauthorized';
    case 'uninstalled':
    default:
      return 'Uninstalled';
  }
});

const isUninstalled = computed(() => {
  return props.application.status === 'uninstalled';
});

const handleOpenDetail = () => {
  emit('openDetail', props.application.key);
};
</script>

<template>
  <div class="space-y-4 rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800" :data-status="application.status">
    <div class="flex items-center justify-between h-7">
      <!-- Logo & Name -->
      <div class="flex items-center">
        <!-- Generic icon placeholder - in real app, this would be dynamic based on application -->
        <svg
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

      <!-- Status Label -->
      <span class="text-xs font-medium px-2.5 py-0.5 rounded" :class="statusBadgeClass">
        {{ statusLabel }}
      </span>
    </div>

    <div class="min-h-[3rem]">
      <p class="text-gray-500 dark:text-gray-400">{{ application.description }}</p>
    </div>

    <!-- Action Buttons -->
    <div class="flex items-center gap-2">
      <Button
        v-if="isUninstalled"
        variant="primary"
        class="flex-1"
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

