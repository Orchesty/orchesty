<script setup lang="ts">
interface Props {
  modelValue: string
  placeholder?: string
  width?: string
  type?: 'text' | 'number'
}

const props = withDefaults(defineProps<Props>(), {
  placeholder: '',
  width: 'w-48',
  type: 'text',
})

const emit = defineEmits<{
  'update:modelValue': [value: string]
}>()

const handleInput = (event: Event) => {
  const target = event.target as HTMLInputElement
  emit('update:modelValue', target.value)
}
</script>

<template>
  <input
    :type="type"
    :value="modelValue"
    @input="handleInput"
    :placeholder="placeholder"
    :class="[
      width,
      'rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500'
    ]"
  />
</template>

