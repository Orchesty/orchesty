<script setup lang="ts">
import { RouterView } from 'vue-router'
import Toast from '@/components/ui/Toast.vue'
import { useToast } from '@/composables/useToast'
import { useTopologyNodeMappings } from '@/composables/useTopologyNodeMappings'

const { toasts, removeToast } = useToast()

const { loadMappings } = useTopologyNodeMappings()
loadMappings()
</script>

<template>
  <RouterView />

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
