<!-- eslint-disable vue/multi-word-component-names -->
<script setup lang="ts">
interface Props {
  modelValue: string | null
  label?: string
  placeholder?: string
  type?: 'text' | 'email' | 'password' | 'number'
  required?: boolean
  disabled?: boolean
  readonly?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  label: '',
  placeholder: '',
  type: 'text',
  required: false,
  disabled: false,
  readonly: false,
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
  <div>
    <label
      v-if="label"
      class="mb-2 flex text-sm font-medium text-gray-900 dark:text-white"
    >
      {{ label }}{{ required ? '*' : '' }}
    </label>
    <input
      :type="type"
      :value="modelValue || ''"
      @input="handleInput"
      :placeholder="placeholder"
      :required="required"
      :disabled="disabled"
      :readonly="readonly"
      class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
      :class="{
        'cursor-not-allowed': disabled || readonly
      }"
    />
  </div>
</template>

