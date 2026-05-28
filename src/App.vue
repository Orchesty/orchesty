<script setup lang="ts">
import { computed } from 'vue'
import { RouterView, useRoute } from 'vue-router'
import Toast from '@/components/ui/Toast.vue'
import LoadingSpinner from '@/components/ui/LoadingSpinner.vue'
import { useToast } from '@/composables/useToast'
import { useTopologyNodeMappings } from '@/composables/useTopologyNodeMappings'
import { useActivityTracker } from '@/composables/useActivityTracker'
import { useAuthStore } from '@/stores/auth'

const { toasts, removeToast } = useToast()
const route = useRoute()
const authStore = useAuthStore()

const { startTracking } = useActivityTracker()
startTracking()

const { loadMappings, isReady } = useTopologyNodeMappings()
loadMappings()

const publicPaths = ['/sign-in', '/setup', '/forgot-password', '/reset-password', '/accept-invite']
const isPublicRoute = computed(() =>
  publicPaths.some(p => route.path === p || route.path.startsWith(p + '/'))
)

const appReady = computed(() => isPublicRoute.value || !authStore.isAuthenticated || isReady.value)
</script>

<template>
  <RouterView v-if="appReady" />
  <div v-else class="flex h-screen items-center justify-center bg-gray-50 dark:bg-gray-900">
    <LoadingSpinner size="lg" text="Loading application data..." />
  </div>

  <!-- Global Toast Notifications -->
  <div class="fixed bottom-4 left-4 z-[80] flex flex-col gap-2">
    <Toast
      v-for="toast in toasts"
      :key="toast.id"
      :id="toast.id"
      :message="toast.message"
      :type="toast.type"
      :duration="toast.duration"
      @close="removeToast"
    />
  </div>
</template>
