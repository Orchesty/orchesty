<script setup lang="ts">
import { ref } from 'vue'

interface Props {
  loading?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  loading: false
})

const emit = defineEmits<{
  send: [message: string]
}>()

const message = ref('')
const textareaRef = ref<HTMLTextAreaElement | null>(null)

const autoResize = () => {
  const el = textareaRef.value
  if (!el) return
  el.style.height = 'auto'
  el.style.height = el.scrollHeight + 'px'
}

const handleSend = () => {
  const trimmedMessage = message.value.trim()
  if (trimmedMessage && !props.loading) {
    emit('send', trimmedMessage)
    message.value = ''
    if (textareaRef.value) {
      textareaRef.value.style.height = 'auto'
    }
  }
}

const handleKeydown = (event: KeyboardEvent) => {
  if (event.key === 'Enter' && !event.shiftKey) {
    event.preventDefault()
    handleSend()
  }
}
</script>

<template>
  <div class="absolute bottom-0 left-0 right-0 z-10">
    <div class="max-w-4xl mx-auto px-4 py-3">
      <form @submit.prevent="handleSend">
        <div class="flex items-center gap-4 border border-gray-300 rounded-lg bg-white px-3 py-2 dark:bg-gray-800 dark:border-gray-600">
          <label for="ai-chat-input" class="sr-only">Write message</label>
          <textarea
            ref="textareaRef"
            id="ai-chat-input"
            v-model="message"
            @keydown="handleKeydown"
            @input="autoResize"
            :disabled="loading"
            placeholder="Write a prompt..."
            rows="1"
            class="block flex-1 border-0 bg-transparent px-0 text-sm text-gray-800 focus:ring-0 resize-none overflow-y-auto max-h-[50vh] dark:text-white dark:placeholder:text-gray-400"
          ></textarea>
          <button
            type="submit"
            :disabled="!message.trim() || loading"
            class="inline-flex cursor-pointer justify-center rounded-full p-2 text-primary-600 hover:bg-primary-100 dark:text-primary-500 dark:hover:bg-gray-600 flex-shrink-0 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <template v-if="loading">
              <svg aria-hidden="true" role="status" class="h-4 w-4 animate-spin" viewBox="0 0 100 101" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z" fill="currentColor"/>
                <path d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z" fill="currentColor"/>
              </svg>
            </template>
            <template v-else>
              <svg class="h-4 w-4 rotate-90 rtl:-rotate-90" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 18 20">
                <path d="m17.914 18.594-8-18a1 1 0 0 0-1.828 0l-8 18a1 1 0 0 0 1.157 1.376L8 18.281V9a1 1 0 0 1 2 0v9.281l6.758 1.689a1 1 0 0 0 1.156-1.376Z" />
              </svg>
            </template>
            <span class="sr-only">Send message</span>
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

