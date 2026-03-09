<!-- eslint-disable vue/multi-word-component-names -->
<script setup lang="ts">
import { watch, nextTick, onMounted, onBeforeUnmount, ref } from 'vue'

interface Props {
  modelValue: boolean
  id: string
  title: string
  size?: 'sm' | 'md' | 'lg' | 'xl' | '4xl'
}

const props = withDefaults(defineProps<Props>(), {
  size: 'md',
})

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  'shown': []
}>()

// eslint-disable-next-line @typescript-eslint/no-explicit-any
const modalInstance = ref<any>(null)

const handleClose = () => {
  emit('update:modelValue', false)
}

// Initialize Flowbite modal when component mounts
onMounted(async () => {
  await nextTick()
  
  const modalElement = document.getElementById(props.id)
  
  if (modalElement) {
    // Import Modal from Flowbite
    const { Modal } = await import('flowbite')
    
    // Create modal instance once
    modalInstance.value = new Modal(modalElement, {
      placement: 'center',
      backdrop: 'dynamic',
      backdropClasses: 'bg-gray-900/50 dark:bg-gray-900/80 fixed inset-0 z-[65]',
      closable: true,
      onHide: () => {
        // Synchronize Vue state when modal is hidden by Flowbite (e.g., clicking backdrop)
        emit('update:modelValue', false)
      },
      onShow: () => {
        const el = document.getElementById(props.id)
        if (el) {
          const onTransitionEnd = () => {
            emit('shown')
            el.removeEventListener('transitionend', onTransitionEnd)
          }
          el.addEventListener('transitionend', onTransitionEnd)
        }
      },
    })
  }
})

// Watch for modelValue changes from parent and trigger Flowbite modal
watch(
  () => props.modelValue,
  async (newValue) => {
    await nextTick()
    
    if (modalInstance.value) {
      if (newValue) {
        modalInstance.value.show()
      } else {
        modalInstance.value.hide()
      }
    }
  },
)

// Cleanup on unmount
onBeforeUnmount(() => {
  if (modalInstance.value) {
    modalInstance.value.hide()
  }
})

// Size class mapping
const sizeClass = {
  sm: 'max-w-sm',
  md: 'max-w-md',
  lg: 'max-w-lg',
  xl: 'max-w-xl',
  '4xl': 'max-w-4xl',
}
</script>

<template>
  <div
    :id="id"
    tabindex="-1"
    aria-hidden="true"
    class="fixed left-0 right-0 top-0 z-[70] hidden h-[calc(100%-1rem)] max-h-full w-full items-center justify-center overflow-y-auto overflow-x-hidden md:inset-0"
  >
    <div :class="['relative max-h-full w-full p-4', sizeClass[size]]">
      <!-- Modal content -->
      <div class="relative rounded-lg bg-white shadow dark:bg-gray-800">
        <!-- Modal header -->
        <div
          class="flex items-center justify-between rounded-t border-b p-4 dark:border-gray-600 md:p-5"
        >
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
            {{ title }}
          </h3>
          <button
            type="button"
            class="ms-auto inline-flex h-8 w-8 items-center justify-center rounded-lg bg-transparent text-sm text-gray-400 hover:bg-gray-200 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
            :data-modal-hide="id"
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
            <span class="sr-only">Close modal</span>
          </button>
        </div>
        <!-- Modal body -->
        <div class="p-4 md:p-5">
          <slot></slot>
        </div>
        <!-- Modal footer -->
        <div
          v-if="$slots['footer-actions']"
          class="flex items-center justify-end gap-3 border-t border-gray-200 p-4 dark:border-gray-600 md:p-5"
        >
          <slot name="footer-actions"></slot>
        </div>
      </div>
    </div>
  </div>
</template>

