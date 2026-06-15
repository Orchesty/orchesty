<script setup lang="ts">
import { computed } from 'vue'
import Input from '@/components/ui/Input.vue'
import Checkbox from '@/components/ui/Checkbox.vue'
import DropdownMenu, { type DropdownMenuSection } from '@/components/ui/DropdownMenu.vue'
import { MCP_PARAM_TYPES, type McpParam, type McpParamType } from '@/utils/mcpManifest'

interface Props {
  modelValue: McpParam
  disabled?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  disabled: false,
})

const emit = defineEmits<{
  'update:modelValue': [value: McpParam]
  remove: []
}>()

function patch<K extends keyof McpParam>(key: K, value: McpParam[K]) {
  emit('update:modelValue', { ...props.modelValue, [key]: value })
}

const isString = computed(() => props.modelValue.type === 'string')
const isNumeric = computed(
  () => props.modelValue.type === 'number' || props.modelValue.type === 'integer',
)
const isEnum = computed(() => props.modelValue.type === 'enum')

const typeDropdownId = `mcp-param-type-${Math.random().toString(36).slice(2, 9)}`

const checkIcon =
  '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4 text-primary-600 dark:text-primary-400"><path fill-rule="evenodd" d="M16.704 5.29a1 1 0 010 1.42l-7.5 7.5a1 1 0 01-1.42 0l-3.5-3.5a1 1 0 111.42-1.42l2.79 2.79 6.79-6.79a1 1 0 011.42 0z" clip-rule="evenodd" /></svg>'

const typeDropdownSections = computed<DropdownMenuSection[]>(() => [
  {
    items: MCP_PARAM_TYPES.map(type => {
      const isSelected = type === props.modelValue.type
      return {
        type: 'button' as const,
        label: type,
        icon: isSelected ? checkIcon : '<span class="inline-block h-4 w-4"></span>',
        class: isSelected
          ? 'bg-gray-50 font-medium text-gray-900 dark:bg-gray-700 dark:text-white'
          : '',
        onClick: () => patch('type', type as McpParamType),
      }
    }),
  },
])
</script>

<template>
  <div class="rounded-lg border border-gray-200 bg-gray-50/60 p-3 dark:border-gray-700 dark:bg-gray-800/40">
    <!-- Primary row: name / type / required / remove -->
    <div class="flex flex-wrap items-end gap-3">
      <div class="min-w-[160px] flex-1">
        <label class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-400">Name</label>
        <Input
          :model-value="modelValue.name"
          :disabled="disabled"
          placeholder="e.g. location"
          @update:model-value="patch('name', String($event))"
        />
      </div>

      <div class="w-[140px]">
        <label class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-400">Type</label>
        <DropdownMenu :id="typeDropdownId" :sections="typeDropdownSections" width="w-full" block>
          <template #trigger>
            <span
              class="flex w-full items-center justify-between rounded-lg border border-gray-300 bg-gray-50 px-3 py-2 text-sm text-gray-900 hover:bg-gray-100 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:hover:bg-gray-600"
              :class="{ 'pointer-events-none opacity-50': disabled }"
            >
              <span class="truncate">{{ modelValue.type }}</span>
              <svg class="ms-2 h-4 w-4 shrink-0" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 4 4 4-4" />
              </svg>
            </span>
          </template>
        </DropdownMenu>
      </div>

      <div class="pb-2.5">
        <Checkbox
          :model-value="modelValue.required"
          :disabled="disabled"
          label="Required"
          @update:model-value="patch('required', $event)"
        />
      </div>

      <button
        type="button"
        :disabled="disabled"
        class="ml-auto inline-flex items-center rounded-lg p-1 text-sm font-medium text-gray-500 hover:bg-gray-200 hover:text-gray-900 focus:outline-hidden disabled:opacity-50 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
        title="Remove parameter"
        @click="emit('remove')"
      >
        <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="currentColor"><path d="M292.31-140q-29.92 0-51.12-21.19Q220-182.39 220-212.31V-720h-40v-60h180v-35.38h240V-780h180v60h-40v507.69Q740-182 719-161q-21 21-51.31 21H292.31ZM680-720H280v507.69q0 5.39 3.46 8.85t8.85 3.46h375.38q4.62 0 8.46-3.85 3.85-3.84 3.85-8.46V-720ZM376.16-280h59.99v-360h-59.99v360Zm147.69 0h59.99v-360h-59.99v360ZM280-720v520-520Z"/></svg>
        <span class="sr-only">Remove parameter</span>
      </button>
    </div>

    <!-- Description -->
    <div class="mt-3">
      <label class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-400">Description</label>
      <Input
        :model-value="modelValue.description"
        :disabled="disabled"
        placeholder="What this parameter means — shown to the assistant"
        @update:model-value="patch('description', String($event))"
      />
    </div>

    <!-- Constraints -->
    <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
      <div v-if="isEnum">
        <label class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-400">
          Allowed values (comma separated)
        </label>
        <Input
          :model-value="modelValue.enumValues"
          :disabled="disabled"
          placeholder="cs, en, de"
          @update:model-value="patch('enumValues', String($event))"
        />
      </div>

      <div v-if="isString">
        <label class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-400">Format</label>
        <Input
          :model-value="modelValue.format"
          :disabled="disabled"
          placeholder="date, date-time, email, uri…"
          @update:model-value="patch('format', String($event))"
        />
      </div>

      <div v-if="isString">
        <label class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-400">Pattern (regex)</label>
        <Input
          :model-value="modelValue.pattern"
          :disabled="disabled"
          placeholder="^[A-Z]{3}$"
          @update:model-value="patch('pattern', String($event))"
        />
      </div>

      <div v-if="isNumeric">
        <label class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-400">Minimum</label>
        <Input
          type="number"
          :model-value="modelValue.minimum"
          :disabled="disabled"
          @update:model-value="patch('minimum', String($event))"
        />
      </div>

      <div v-if="isNumeric">
        <label class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-400">Maximum</label>
        <Input
          type="number"
          :model-value="modelValue.maximum"
          :disabled="disabled"
          @update:model-value="patch('maximum', String($event))"
        />
      </div>

      <div>
        <label class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-400">Default (optional)</label>
        <Input
          :model-value="modelValue.default"
          :disabled="disabled"
          placeholder="leave empty for none"
          @update:model-value="patch('default', String($event))"
        />
      </div>
    </div>
  </div>
</template>
