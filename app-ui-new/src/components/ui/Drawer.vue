<script setup lang="ts">
import { watch, nextTick, onMounted, onBeforeUnmount, ref } from 'vue'
import Button from './Button.vue'

interface Props {
  modelValue: boolean
  id: string
  label: string
  width?: string
}

const props = withDefaults(defineProps<Props>(), {
  width: 'w-1/2 min-w-[500px]',
})

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
}>()

// eslint-disable-next-line @typescript-eslint/no-explicit-any
const drawerInstance = ref<any>(null)

const handleClose = () => {
  emit('update:modelValue', false)
}

// Initialize Flowbite drawer when component mounts
onMounted(async () => {
  await nextTick()
  
  const drawerElement = document.getElementById(props.id)
  if (drawerElement) {
    // Import Drawer from Flowbite
    const { Drawer } = await import('flowbite')
    
    // Create drawer instance once
    drawerInstance.value = new Drawer(drawerElement, {
      placement: 'right',
      backdrop: true,
      bodyScrolling: false,
      edge: false,
      edgeOffset: '',
      backdropClasses: 'bg-gray-900/50 dark:bg-gray-900/80 fixed inset-0 z-30',
      onHide: () => {
        // Synchronize Vue state when drawer is hidden by Flowbite (e.g., clicking backdrop)
        emit('update:modelValue', false)
      },
      onShow: () => {
        // Don't emit here to avoid circular updates
      },
    })
  }
})

// Watch for modelValue changes from parent and trigger Flowbite drawer
watch(
  () => props.modelValue,
  async (newValue) => {
    await nextTick()
    
    if (drawerInstance.value) {
      if (newValue) {
        drawerInstance.value.show()
      } else {
        drawerInstance.value.hide()
      }
    }
  },
)

// Cleanup on unmount
onBeforeUnmount(() => {
  if (drawerInstance.value) {
    drawerInstance.value.hide()
  }
})
</script>

<template>
  <div
    :id="id"
    :class="[
      'fixed top-0 right-0 z-40 h-screen p-4 overflow-y-auto transition-transform translate-x-full bg-white dark:bg-gray-800',
      width,
    ]"
    tabindex="-1"
    :aria-labelledby="`${id}-title`"
  >
    <!-- Label -->
    <h5
      :id="`${id}-title`"
      class="mb-4 inline-flex items-center text-base font-semibold uppercase text-gray-500 dark:text-gray-400"
    >
      {{ label }}
    </h5>

    <!-- Close Button (Top Right) -->
    <button
      type="button"
      :data-drawer-hide="id"
      :aria-controls="id"
      class="absolute right-2.5 top-2.5 inline-flex h-8 w-8 items-center justify-center rounded-lg bg-transparent text-sm text-gray-400 hover:bg-gray-200 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
      @click="handleClose"
    >
      <svg
        class="h-3 w-3"
        aria-hidden="true"
        xmlns="http://www.w3.org/2000/svg"
        fill="none"
        viewBox="0 0 14 14"
      >
        <path
          stroke="currentColor"
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"
        />
      </svg>
      <span class="sr-only">Close menu</span>
    </button>

    <!-- Header Actions Slot -->
    <div v-if="$slots['header-actions']" class="mb-6">
      <slot name="header-actions"></slot>
    </div>

    <!-- Content Slot -->
    <div>
      <slot></slot>
    </div>

    <!-- Footer -->
    <div
      class="mt-6 flex items-center justify-end border-t border-gray-200 pt-6 dark:border-gray-700"
    >
      <slot name="footer-actions">
        <!-- Default Close Button -->
        <Button variant="outline" :data-drawer-hide="id" @click="handleClose">
          Close
        </Button>
      </slot>
    </div>
  </div>
</template>

