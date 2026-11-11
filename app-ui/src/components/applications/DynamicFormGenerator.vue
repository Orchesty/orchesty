<script setup lang="ts">
import { ref } from 'vue';
import type { ApplicationSetting } from '@/types/applications';
import { groupKeyOf } from '@/services/applicationsService';
import FormInput from '@/components/ui/FormInput.vue';

interface Props {
  settings: ApplicationSetting[];
  // Value model is scoped per form: { [formKey]: { [fieldKey]: value } }.
  modelValue: Record<string, Record<string, unknown>>;
}

const props = defineProps<Props>();

const emit = defineEmits<{
  'update:modelValue': [value: Record<string, Record<string, unknown>>];
}>();

const errors = ref<Record<string, string>>({});

const valueOf = (setting: ApplicationSetting): unknown =>
  props.modelValue[groupKeyOf(setting)]?.[setting.key];

// Form-scoped identity for a field, used for the error map, the v-for key and the DOM id/label.
// Mirrors the value model so two forms that share a field key on one tab stay separate.
const fieldId = (setting: ApplicationSetting): string => `${groupKeyOf(setting)}:${setting.key}`;

const updateValue = (setting: ApplicationSetting, value: unknown) => {
  if (errors.value[fieldId(setting)]) {
    delete errors.value[fieldId(setting)];
  }
  const formKey = groupKeyOf(setting);
  emit('update:modelValue', {
    ...props.modelValue,
    [formKey]: {
      ...props.modelValue[formKey],
      [setting.key]: value,
    },
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
  const value = valueOf(setting) ?? setting.value;
  if (typeof value === 'boolean') return value;
  if (typeof value === 'string') return value === 'true';
  return false;
};

const handleCheckboxChange = (setting: ApplicationSetting, event: Event) => {
  const target = event.target as HTMLInputElement;
  updateValue(setting, target.checked);
};

const handleSelectChange = (setting: ApplicationSetting, event: Event) => {
  const target = event.target as HTMLSelectElement;
  updateValue(setting, target.value);
};

const handleMultiSelectChange = (setting: ApplicationSetting, event: Event) => {
  const target = event.target as HTMLSelectElement;
  updateValue(setting, Array.from(target.selectedOptions).map((option) => option.value));
};

const isMultiSelected = (setting: ApplicationSetting, value: string): boolean => {
  const current = valueOf(setting) ?? setting.value;
  return Array.isArray(current) && current.includes(value);
};


const validate = (): boolean => {
  const newErrors: Record<string, string> = {};
  for (const setting of props.settings) {
    if (!setting.required) continue
    const value = valueOf(setting)
    const isEmpty = value === undefined || value === null || value === ''
      || (Array.isArray(value) && value.length === 0);
    if (isEmpty) {
      newErrors[fieldId(setting)] = 'This field is required';
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
    <div v-for="setting in settings" :key="fieldId(setting)">
      <!-- Text, URL, Password, Number inputs -->
      <div v-if="['text', 'url', 'password', 'number'].includes(setting.type)">
        <label
          :for="fieldId(setting)"
          class="block mb-2 text-sm font-medium text-gray-900 dark:text-white"
        >
          {{ setting.label }}
          <span v-if="setting.required" class="text-red-500">*</span>
        </label>
        <FormInput
          :id="fieldId(setting)"
          :type="getInputType(setting.type) as 'text' | 'number' | 'email' | 'password'"
          :model-value="String(valueOf(setting) ?? setting.value ?? '')"
          :placeholder="setting.label"
          :disabled="setting.disabled || setting.readOnly"
          :required="setting.required"
          :error="!!errors[fieldId(setting)]"
          width="w-2/3"
          @update:model-value="(value) => updateValue(setting, value)"
        />
        <p v-if="errors[fieldId(setting)]" class="mt-1 text-xs text-red-500 dark:text-red-400">
          {{ errors[fieldId(setting)] }}
        </p>
        <p v-else-if="setting.description" class="mt-1 text-xs text-gray-500 dark:text-gray-400">
          {{ setting.description }}
        </p>
      </div>

      <!-- Selectbox -->
      <div v-else-if="setting.type === 'selectbox'">
        <label
          :for="fieldId(setting)"
          class="block mb-2 text-sm font-medium text-gray-900 dark:text-white"
        >
          {{ setting.label }}
          <span v-if="setting.required" class="text-red-500">*</span>
        </label>
        <select
          :id="fieldId(setting)"
          :value="String(valueOf(setting) ?? setting.value ?? '')"
          :disabled="setting.disabled || setting.readOnly"
          :required="setting.required"
          :class="[
            'block w-2/3 rounded-lg border bg-gray-50 p-2.5 text-sm text-gray-900 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400',
            errors[fieldId(setting)]
              ? 'border-red-500 focus:border-red-500 focus:ring-red-500 dark:border-red-500'
              : 'border-gray-300 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:focus:border-primary-500 dark:focus:ring-primary-500'
          ]"
          @change="(e) => handleSelectChange(setting, e)"
        >
          <option value="">Select...</option>
          <option
            v-for="choice in setting.choices"
            :key="choice.value"
            :value="choice.value"
          >
            {{ choice.label }}
          </option>
        </select>
        <p v-if="errors[fieldId(setting)]" class="mt-1 text-xs text-red-500 dark:text-red-400">
          {{ errors[fieldId(setting)] }}
        </p>
        <p v-else-if="setting.description" class="mt-1 text-xs text-gray-500 dark:text-gray-400">
          {{ setting.description }}
        </p>
      </div>

      <!-- Multiselect -->
      <div v-else-if="setting.type === 'multiselect'">
        <label
          :for="fieldId(setting)"
          class="block mb-2 text-sm font-medium text-gray-900 dark:text-white"
        >
          {{ setting.label }}
          <span v-if="setting.required" class="text-red-500">*</span>
        </label>
        <select
          :id="fieldId(setting)"
          multiple
          :disabled="setting.disabled || setting.readOnly"
          :required="setting.required"
          :class="[
            'block w-2/3 rounded-lg border bg-gray-50 p-2.5 text-sm text-gray-900 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400',
            errors[fieldId(setting)]
              ? 'border-red-500 focus:border-red-500 focus:ring-red-500 dark:border-red-500'
              : 'border-gray-300 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:focus:border-primary-500 dark:focus:ring-primary-500'
          ]"
          @change="(e) => handleMultiSelectChange(setting, e)"
        >
          <option
            v-for="choice in setting.choices"
            :key="choice.value"
            :value="choice.value"
            :selected="isMultiSelected(setting, choice.value)"
          >
            {{ choice.label }}
          </option>
        </select>
        <p v-if="errors[fieldId(setting)]" class="mt-1 text-xs text-red-500 dark:text-red-400">
          {{ errors[fieldId(setting)] }}
        </p>
        <p v-else-if="setting.description" class="mt-1 text-xs text-gray-500 dark:text-gray-400">
          {{ setting.description }}
        </p>
      </div>

      <!-- Checkbox -->
      <div v-else-if="setting.type === 'checkbox'" class="flex items-start">
        <div class="flex items-center h-5">
          <input
            :id="fieldId(setting)"
            type="checkbox"
            :checked="getCheckboxValue(setting)"
            :disabled="setting.disabled || setting.readOnly"
            class="w-4 h-4 text-primary-600 bg-gray-100 border-gray-300 rounded-sm focus:ring-primary-500 dark:focus:ring-primary-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
            @change="(e) => handleCheckboxChange(setting, e)"
          >
        </div>
        <div class="ml-2">
          <label
            :for="fieldId(setting)"
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

