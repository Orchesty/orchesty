import { ref } from 'vue'
import type { Toast, ToastType } from '@/types/toast'

const toasts = ref<Toast[]>([])
const MAX_TOASTS = 3

let toastIdCounter = 0

export function useToast() {
  const showToast = (message: string, type: ToastType = 'info', duration: number = 5000) => {
    const id = `toast-${++toastIdCounter}-${Date.now()}`
    
    const newToast: Toast = {
      id,
      message,
      type,
      duration,
    }
    
    // Add new toast
    toasts.value.push(newToast)
    
    // If we exceed max toasts, remove the oldest one (FIFO)
    if (toasts.value.length > MAX_TOASTS) {
      toasts.value.shift()
    }
  }
  
  const removeToast = (id: string) => {
    const index = toasts.value.findIndex((toast) => toast.id === id)
    if (index !== -1) {
      toasts.value.splice(index, 1)
    }
  }
  
  return {
    toasts,
    showToast,
    removeToast,
  }
}

