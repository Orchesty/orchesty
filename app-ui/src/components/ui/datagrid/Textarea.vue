<script setup lang="ts">
interface Props {
  modelValue: string
  placeholder?: string
  rows?: number
  error?: string
  id?: string
}

const props = withDefaults(defineProps<Props>(), {
  placeholder: '',
  rows: 4,
  error: '',
  id: '',
})

const emit = defineEmits<{
  'update:modelValue': [value: string]
}>()
</script>

<template>
  <div>
    <textarea
      :id="id"
      :value="modelValue"
      :rows="rows"
      :placeholder="placeholder"
      class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 font-mono text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
      :class="{ 'border-red-500 focus:border-red-500 focus:ring-red-500': error }"
      @input="emit('update:modelValue', ($event.target as HTMLTextAreaElement).value)"
    ></textarea>
    <p v-if="error" class="mt-1 text-sm text-red-600 dark:text-red-500">
      {{ error }}
    </p>
  </div>
</template>

