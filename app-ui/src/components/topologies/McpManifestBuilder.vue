<script setup lang="ts">
import { reactive, ref, computed, watch } from 'vue'
import Button from '@/components/ui/Button.vue'
import Card from '@/components/ui/Card.vue'
import Checkbox from '@/components/ui/Checkbox.vue'
import Input from '@/components/ui/Input.vue'
import StatusBadge from '@/components/ui/StatusBadge.vue'
import Textarea from '@/components/ui/datagrid/Textarea.vue'
import McpParamRow from '@/components/topologies/McpParamRow.vue'
import { validateMcpManifest } from '@/utils/mcpManifestValidator'
import {
  formFromManifest,
  manifestFromForm,
  deriveKindFromNodes,
  describeParamsForLlm,
  emptyParam,
  emptyExample,
  type McpManifestForm,
} from '@/utils/mcpManifest'

interface Props {
  modelValue: Record<string, unknown>
  topologyNodes?: unknown[]
  topologyName?: string
  disabled?: boolean
  /** What the manifest describes: a topology (kind derived from nodes) or an Action (always returns data) */
  subject?: 'topology' | 'action'
  /** Tool id shown in the preview; defaults to `topology:<name>` */
  toolId?: string
}

const props = withDefaults(defineProps<Props>(), {
  topologyNodes: () => [],
  topologyName: '',
  disabled: false,
  subject: 'topology',
  toolId: '',
})

const emit = defineEmits<{
  'update:modelValue': [value: Record<string, unknown>]
  'update:valid': [value: boolean]
}>()

const form = reactive<McpManifestForm>(formFromManifest(props.modelValue))
const mode = ref<'builder' | 'advanced'>('builder')
const rawText = ref('')
const rawError = ref('')
const validationError = ref('')

let lastEmitted = JSON.stringify(props.modelValue ?? {})

const isAction = computed(() => props.subject === 'action')
const derived = computed(() =>
  isAction.value ? { kind: 'query' as const, responseNodeName: null } : deriveKindFromNodes(props.topologyNodes),
)
const isQuery = computed(() => derived.value.kind === 'query')

const toolId = computed(() => {
  if (props.toolId !== '') return props.toolId
  const name = props.topologyName.trim()
  return name === '' ? 'topology:{name}' : `topology:${name}`
})

const llmPreviewParams = computed(() => describeParamsForLlm(form.inputParams))

function emitState(manifest: Record<string, unknown>): void {
  lastEmitted = JSON.stringify(manifest)
  emit('update:modelValue', manifest)

  const result = validateMcpManifest(manifest)
  validationError.value = result.valid ? '' : result.error || 'Validation failed'
  emit('update:valid', result.valid)
}

// Builder mode is the source of truth: any form change rebuilds the manifest.
watch(
  form,
  () => {
    if (mode.value !== 'builder') return
    emitState(manifestFromForm(form))
  },
  { deep: true },
)

// External reloads (different topology / version) repopulate the form without
// echoing back to the parent.
watch(
  () => props.modelValue,
  val => {
    const str = JSON.stringify(val ?? {})
    if (str === lastEmitted) return
    Object.assign(form, formFromManifest(val))
    lastEmitted = str
    if (mode.value === 'advanced') {
      rawText.value = JSON.stringify(manifestFromForm(form), null, 2)
    }
  },
)

function switchMode(next: 'builder' | 'advanced'): void {
  if (next === mode.value) return
  if (next === 'advanced') {
    rawText.value = JSON.stringify(manifestFromForm(form), null, 2)
    rawError.value = ''
  }
  mode.value = next
}

function onRawInput(text: string): void {
  rawText.value = text
  let parsed: unknown
  try {
    parsed = JSON.parse(text)
  } catch {
    rawError.value = 'Invalid JSON syntax'
    emit('update:valid', false)
    return
  }
  rawError.value = ''
  const obj =
    typeof parsed === 'object' && parsed !== null && !Array.isArray(parsed)
      ? (parsed as Record<string, unknown>)
      : {}
  // Keep the structured form in sync so toggling back preserves edits.
  Object.assign(form, formFromManifest(obj))
  emitState(obj)
}

function addInputParam(): void {
  form.inputParams.push(emptyParam())
}

function removeInputParam(index: number): void {
  form.inputParams.splice(index, 1)
}

function addOutputParam(): void {
  form.outputParams.push(emptyParam())
}

function removeOutputParam(index: number): void {
  form.outputParams.splice(index, 1)
}

function addExample(): void {
  form.examples.push(emptyExample())
}

function removeExample(index: number): void {
  form.examples.splice(index, 1)
}
</script>

<template>
  <div class="space-y-6">
    <!-- Header: opt-in + derived kind badge -->
    <Card>
      <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
          <div class="flex items-center gap-3">
            <button
              type="button"
              role="switch"
              :aria-checked="form.exposed"
              :disabled="disabled"
              class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 disabled:opacity-50 dark:focus:ring-offset-gray-900"
              :class="form.exposed ? 'bg-primary-600' : 'bg-gray-300 dark:bg-gray-600'"
              @click="form.exposed = !form.exposed"
            >
              <span
                class="inline-block h-5 w-5 translate-y-0.5 transform rounded-full bg-white shadow transition-transform"
                :class="form.exposed ? 'translate-x-5' : 'translate-x-0.5'"
              />
            </button>
            <div>
              <p class="text-sm font-semibold text-gray-900 dark:text-white">Expose to Trace / Pulse</p>
              <p class="text-xs text-gray-500 dark:text-gray-400">
                When off, this {{ isAction ? 'action' : 'topology' }} is not offered as an MCP tool.
              </p>
            </div>
          </div>
        </div>

        <StatusBadge :variant="isQuery ? 'blue' : 'green'">
          {{ isQuery ? 'Query — returns data' : 'Command — runs a process' }}
        </StatusBadge>
      </div>

      <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">
        <template v-if="isAction">
          A read action returns the target system's response directly to the caller.
        </template>
        <template v-else-if="isQuery">
          Kind detected from the topology: it ends with a Response node
          <code v-if="derived.responseNodeName">{{ derived.responseNodeName }}</code>, so it returns
          data to the caller.
        </template>
        <template v-else>
          Kind detected from the topology: no Response node found, so it just runs the process
          (fire-and-forget). Add a <strong>Response</strong> node to return data.
        </template>
      </p>
    </Card>

    <!-- Mode switch -->
    <div class="inline-flex">
      <button
        type="button"
        :aria-pressed="mode === 'builder'"
        class="rounded-l-full px-3 py-2 text-sm font-medium focus:outline-hidden"
        :class="mode === 'builder'
          ? 'border border-primary-700 bg-primary-700 text-white hover:bg-primary-800 dark:border-primary-600 dark:bg-primary-600 dark:hover:bg-primary-700'
          : 'border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 hover:text-primary-700 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white'"
        @click="switchMode('builder')"
      >
        Builder
      </button>
      <button
        type="button"
        :aria-pressed="mode === 'advanced'"
        class="rounded-r-full border-l-0 px-3 py-2 text-sm font-medium focus:outline-hidden"
        :class="mode === 'advanced'
          ? 'border border-primary-700 bg-primary-700 text-white hover:bg-primary-800 dark:border-primary-600 dark:bg-primary-600 dark:hover:bg-primary-700'
          : 'border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 hover:text-primary-700 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white'"
        @click="switchMode('advanced')"
      >
        Advanced (raw JSON)
      </button>
    </div>

    <!-- Advanced raw editor -->
    <div v-if="mode === 'advanced'">
      <Textarea
        :model-value="rawText"
        :rows="20"
        :error="rawError"
        @update:model-value="onRawInput"
      />
    </div>

    <!-- Structured builder -->
    <template v-else>
      <!-- Identity -->
      <div class="space-y-3">
        <div>
          <label class="mb-1 block text-sm font-medium text-gray-900 dark:text-white">
            Description
            <span v-if="form.exposed" class="text-red-500">*</span>
          </label>
          <Textarea
            :model-value="form.description"
            :rows="3"
            placeholder="What the topology does and when to use it — the main signal the assistant uses to pick this tool."
            @update:model-value="form.description = $event"
          />
        </div>
      </div>

      <!-- Input parameters -->
      <div class="space-y-3">
        <div class="flex flex-col items-start gap-2">
          <div>
            <h4 class="text-sm font-semibold text-gray-900 dark:text-white">Input parameters</h4>
            <p class="text-xs text-gray-500 dark:text-gray-400">
              Mark mandatory ones as Required — the assistant asks the user for any missing required
              value before running.
            </p>
          </div>
          <Button type="button" variant="outline" :disabled="disabled" @click="addInputParam">
            Add parameter
          </Button>
        </div>

        <p v-if="form.inputParams.length === 0" class="text-sm text-gray-400">
          No parameters — the tool runs without arguments.
        </p>

        <div class="space-y-3">
          <McpParamRow
            v-for="(param, index) in form.inputParams"
            :key="`in-${index}`"
            :model-value="param"
            :disabled="disabled"
            @update:model-value="form.inputParams[index] = $event"
            @remove="removeInputParam(index)"
          />
        </div>
      </div>

      <!-- Output parameters (query only) -->
      <div v-if="isQuery" class="space-y-3">
        <div class="flex flex-col items-start gap-2">
          <div>
            <h4 class="text-sm font-semibold text-gray-900 dark:text-white">Output (returned data)</h4>
            <p class="text-xs text-gray-500 dark:text-gray-400">
              Optional — describes the fields the Response node returns, used to present the result.
            </p>
          </div>
          <Button type="button" variant="outline" :disabled="disabled" @click="addOutputParam">
            Add field
          </Button>
        </div>

        <div class="space-y-3">
          <McpParamRow
            v-for="(param, index) in form.outputParams"
            :key="`out-${index}`"
            :model-value="param"
            :disabled="disabled"
            @update:model-value="form.outputParams[index] = $event"
            @remove="removeOutputParam(index)"
          />
        </div>
      </div>

      <!-- Examples -->
      <div class="space-y-3">
        <div class="flex flex-col items-start gap-2">
          <div>
            <h4 class="text-sm font-semibold text-gray-900 dark:text-white">Examples (optional)</h4>
            <p class="text-xs text-gray-500 dark:text-gray-400">
              "What the user says → which arguments" — improves tool selection.
            </p>
          </div>
          <Button type="button" variant="outline" :disabled="disabled" @click="addExample">
            Add example
          </Button>
        </div>

        <div
          v-for="(example, index) in form.examples"
          :key="`ex-${index}`"
          class="rounded-lg border border-gray-200 bg-gray-50/60 p-3 dark:border-gray-700 dark:bg-gray-800/40"
        >
          <div class="flex items-start gap-3">
            <div class="flex-1 space-y-2">
              <div>
                <label class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-400">User says</label>
                <Input
                  :model-value="example.when"
                  :disabled="disabled"
                  placeholder="What's the weather in Prague tomorrow?"
                  @update:model-value="example.when = String($event)"
                />
              </div>
              <div>
                <label class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-400">Arguments (JSON)</label>
                <Textarea
                  :model-value="example.args"
                  :rows="3"
                  placeholder='{ "location": "Prague", "date": "2026-06-11" }'
                  @update:model-value="example.args = $event"
                />
              </div>
            </div>
            <button
              type="button"
              :disabled="disabled"
              class="inline-flex items-center rounded-lg p-1 text-sm font-medium text-gray-500 hover:bg-gray-200 hover:text-gray-900 focus:outline-hidden disabled:opacity-50 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
              title="Remove example"
              @click="removeExample(index)"
            >
              <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="currentColor"><path d="M292.31-140q-29.92 0-51.12-21.19Q220-182.39 220-212.31V-720h-40v-60h180v-35.38h240V-780h180v60h-40v507.69Q740-182 719-161q-21 21-51.31 21H292.31ZM680-720H280v507.69q0 5.39 3.46 8.85t8.85 3.46h375.38q4.62 0 8.46-3.85 3.85-3.84 3.85-8.46V-720ZM376.16-280h59.99v-360h-59.99v360Zm147.69 0h59.99v-360h-59.99v360ZM280-720v520-520Z"/></svg>
              <span class="sr-only">Remove example</span>
            </button>
          </div>
        </div>
      </div>

      <!-- Confirmation -->
      <Card v-if="!isAction">
        <Checkbox
          :model-value="form.requiresConfirmation"
          :disabled="disabled"
          label="Require confirmation before running (recommended for write/command actions)"
          @update:model-value="form.requiresConfirmation = $event"
        />
      </Card>

      <!-- LLM formatting of the response -->
      <Card v-if="!isAction">
        <Checkbox
          :model-value="form.llmFormat"
          :disabled="disabled"
          label="Let the assistant reformat the response with the LLM"
          @update:model-value="form.llmFormat = $event"
        />
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
          Off by default. The response is returned to the caller verbatim and never sent back through
          the LLM, so sensitive data stays out of the model. Enable only when the output is safe to
          summarise; otherwise format it inside the topology.
        </p>
      </Card>

      <!-- Live LLM preview -->
      <Card>
        <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-400">What the assistant sees</p>
        <div class="space-y-1 font-mono text-xs text-gray-700 dark:text-gray-300">
          <div><span class="text-gray-400">tool:</span> {{ toolId }}</div>
          <div v-if="form.description.trim()"><span class="text-gray-400">description:</span> {{ form.description.trim() }}</div>
          <div v-else class="text-amber-600 dark:text-amber-400">description: (missing — required to expose)</div>
          <template v-if="llmPreviewParams.length">
            <div class="text-gray-400">parameters:</div>
            <div v-for="(line, i) in llmPreviewParams" :key="i" class="pl-3">- {{ line }}</div>
          </template>
          <div class="pt-1 text-gray-400">{{ isQuery ? 'returns data (query)' : 'runs a process (command)' }}</div>
        </div>
      </Card>
    </template>

    <p v-if="validationError" class="text-sm text-red-600 dark:text-red-500">{{ validationError }}</p>
  </div>
</template>
