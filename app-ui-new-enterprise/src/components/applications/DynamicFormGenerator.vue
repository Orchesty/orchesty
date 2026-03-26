<script setup lang="ts">
import { computed, ref } from 'vue';
import type { ApplicationSetting } from '@/types/applications';
import FormInput from '@/components/ui/FormInput.vue';

interface Props {
  settings: ApplicationSetting[];
  modelValue: Record<string, unknown>;
}

const props = defineProps<Props>();

const emit = defineEmits<{
  'update:modelValue': [value: Record<string, unknown>];
}>();

const formValues = computed(() => props.modelValue);
const errors = ref<Record<string, string>>({});

const updateValue = (key: string, value: unknown) => {
  if (errors.value[key]) {
    delete errors.value[key];
  }
  emit('update:modelValue', {
    ...formValues.value,
    [key]: value,
  });
};

const getInputType = (type: string): string => {
  switch (type) {
    case 'password':
      return 'password';
    case 'number':
      return 'number';
    case 'url':
    case 'text':
    default:
      return 'text';
  }
};

const getCheckboxValue = (setting: ApplicationSetting): boolean => {
  const value = formValues.value[setting.key] ?? setting.value;
  if (typeof value === 'boolean') return value;
  if (typeof value === 'string') return value === 'true';
  return false;
};

const handleCheckboxChange = (key: string, event: Event) => {
  const target = event.target as HTMLInputElement;
  updateValue(key, target.checked);
};

const handleSelectChange = (key: string, event: Event) => {
  const target = event.target as HTMLSelectElement;
  updateValue(key, target.value);
};


const validate = (): boolean => {
  const newErrors: Record<string, string> = {};
  for (const setting of props.settings) {
    if (!setting.required) continue
    const value = formValues.value[setting.key]
    if (value === undefined || value === null || value === '') {
      newErrors[setting.key] = 'This field is required';
    }
  }
  errors.value = newErrors;
  return Object.keys(newErrors).length === 0;
}

const clearErrors = () => {
  errors.value = {};
}

defineExpose({ validate, clearErrors });
</script>

<template>
  <form v-if="settings && settings.length > 0" class="space-y-4">
    <div v-for="setting in settings" :key="setting.key">
      <!-- Text, URL, Password, Number inputs -->
      <div v-if="['text', 'url', 'password', 'number'].includes(setting.type)">
        <label
          :for="setting.key"
          class="block mb-2 text-sm font-medium text-gray-900 dark:text-white"
        >
          {{ setting.label }}
          <span v-if="setting.required" class="text-red-500">*</span>
        </label>
        <FormInput
          :id="setting.key"
          :type="getInputType(setting.type)"
          :model-value="String(formValues[setting.key] ?? setting.value ?? '')"
          :placeholder="setting.label"
          :disabled="setting.disabled || setting.readOnly"
          :required="setting.required"
          :error="!!errors[setting.key]"
          width="w-2/3"
          @update:model-value="(value) => updateValue(setting.key, value)"
        />
        <p v-if="errors[setting.key]" class="mt-1 text-xs text-red-500 dark:text-red-400">
          {{ errors[setting.key] }}
        </p>
        <p v-else-if="setting.description" class="mt-1 text-xs text-gray-500 dark:text-gray-400">
          {{ setting.description }}
        </p>
      </div>

      <!-- Selectbox -->
      <div v-else-if="setting.type === 'selectbox'">
        <label
          :for="setting.key"
          class="block mb-2 text-sm font-medium text-gray-900 dark:text-white"
        >
          {{ setting.label }}
          <span v-if="setting.required" class="text-red-500">*</span>
        </label>
        <select
          :id="setting.key"
          :value="String(formValues[setting.key] ?? setting.value ?? '')"
          :disabled="setting.disabled || setting.readOnly"
          :required="setting.required"
          :class="[
            'block w-2/3 rounded-lg border bg-gray-50 p-2.5 text-sm text-gray-900 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400',
            errors[setting.key]
              ? 'border-red-500 focus:border-red-500 focus:ring-red-500 dark:border-red-500'
              : 'border-gray-300 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:focus:border-primary-500 dark:focus:ring-primary-500'
          ]"
          @change="(e) => handleSelectChange(setting.key, e)"
        >
          <option value="">Select...</option>
          <option
            v-for="choice in setting.choices"
            :key="choice"
            :value="choice"
          >
            {{ choice }}
          </option>
        </select>
        <p v-if="errors[setting.key]" class="mt-1 text-xs text-red-500 dark:text-red-400">
          {{ errors[setting.key] }}
        </p>
        <p v-else-if="setting.description" class="mt-1 text-xs text-gray-500 dark:text-gray-400">
          {{ setting.description }}
        </p>
      </div>

      <!-- Checkbox -->
      <div v-else-if="setting.type === 'checkbox'" class="flex items-start">
        <div class="flex items-center h-5">
          <input
            :id="setting.key"
            type="checkbox"
            :checked="getCheckboxValue(setting)"
            :disabled="setting.disabled || setting.readOnly"
            class="w-4 h-4 text-primary-600 bg-gray-100 border-gray-300 rounded-sm focus:ring-primary-500 dark:focus:ring-primary-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
            @change="(e) => handleCheckboxChange(setting.key, e)"
          >
        </div>
        <div class="ml-2">
          <label
            :for="setting.key"
            class="text-sm font-medium text-gray-900 dark:text-gray-300"
          >
            {{ setting.label }}
            <span v-if="setting.required" class="text-red-500">*</span>
          </label>
          <p v-if="setting.description" class="text-xs text-gray-500 dark:text-gray-400">
            {{ setting.description }}
          </p>
        </div>
      </div>
    </div>
  </form>
</template>

