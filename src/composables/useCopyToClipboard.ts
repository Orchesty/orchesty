import { ref } from 'vue'

export function useCopyToClipboard() {
  const copied = ref(false)

  const copyToClipboard = async (text: string) => {
    try {
      await navigator.clipboard.writeText(text)
      copied.value = true
      setTimeout(() => {
        copied.value = false
      }, 2000)
      return true
    } catch (err) {
      console.error('Failed to copy:', err)
      return false
    }
  }

  return {
    copied,
    copyToClipboard,
  }
}

