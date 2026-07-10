// Helpers that translate between the structured MCP manifest builder (A2) and
// the stored `mcp_description` JSON Schema. The builder is just a friendlier
// editor on top of the same JSON the validator and backend already understand.
//
// `kind` is intentionally NOT produced here -- it is derived from the topology
// definition (presence of a Response node) on both the UI badge and the backend
// manifest. The builder only authors `exposed`, `description`, `input_schema`,
// `output_schema`, `examples` and `requires_confirmation`.

export type McpParamType =
  | 'string'
  | 'number'
  | 'integer'
  | 'boolean'
  | 'array'
  | 'object'
  | 'enum'

export const MCP_PARAM_TYPES: McpParamType[] = [
  'string',
  'number',
  'integer',
  'boolean',
  'array',
  'object',
  'enum',
]

export interface McpParam {
  name: string
  type: McpParamType
  required: boolean
  description: string
  default: string
  enumValues: string
  format: string
  minimum: string
  maximum: string
  pattern: string
}

export interface McpExample {
  when: string
  args: string
}

export interface McpManifestForm {
  exposed: boolean
  description: string
  inputParams: McpParam[]
  outputParams: McpParam[]
  examples: McpExample[]
  requiresConfirmation: boolean
  llmFormat: boolean
}

export function emptyParam(): McpParam {
  return {
    name: '',
    type: 'string',
    required: false,
    description: '',
    default: '',
    enumValues: '',
    format: '',
    minimum: '',
    maximum: '',
    pattern: '',
  }
}

export function emptyExample(): McpExample {
  return { when: '', args: '' }
}

function isPlainObject(value: unknown): value is Record<string, unknown> {
  return typeof value === 'object' && value !== null && !Array.isArray(value)
}

function coerceDefault(type: McpParamType, raw: string): unknown {
  const value = raw.trim()
  if (value === '') return undefined

  switch (type) {
    case 'number':
    case 'integer': {
      const n = Number(value)
      return Number.isNaN(n) ? value : n
    }
    case 'boolean':
      return value === 'true'
    case 'array':
    case 'object':
      try {
        return JSON.parse(value)
      } catch {
        return value
      }
    default:
      return value
  }
}

function parseEnumValues(raw: string): string[] {
  return raw
    .split(',')
    .map(v => v.trim())
    .filter(v => v !== '')
}

function buildSchemaFromParams(params: McpParam[]): Record<string, unknown> | null {
  const properties: Record<string, unknown> = {}
  const required: string[] = []

  for (const param of params) {
    const name = param.name.trim()
    if (name === '') continue

    const prop: Record<string, unknown> = {}

    if (param.type === 'enum') {
      prop.type = 'string'
      const values = parseEnumValues(param.enumValues)
      if (values.length > 0) prop.enum = values
    } else {
      prop.type = param.type
    }

    if (param.description.trim() !== '') prop.description = param.description.trim()

    if ((param.type === 'string' || param.type === 'enum') && param.format.trim() !== '') {
      prop.format = param.format.trim()
    }
    if (param.type === 'string' && param.pattern.trim() !== '') {
      prop.pattern = param.pattern.trim()
    }
    if ((param.type === 'number' || param.type === 'integer') && param.minimum.trim() !== '') {
      const n = Number(param.minimum)
      if (!Number.isNaN(n)) prop.minimum = n
    }
    if ((param.type === 'number' || param.type === 'integer') && param.maximum.trim() !== '') {
      const n = Number(param.maximum)
      if (!Number.isNaN(n)) prop.maximum = n
    }

    const def = coerceDefault(param.type, param.default)
    if (def !== undefined) prop.default = def

    properties[name] = prop
    if (param.required) required.push(name)
  }

  if (Object.keys(properties).length === 0) return null

  const schema: Record<string, unknown> = {
    type: 'object',
    additionalProperties: false,
    properties,
  }
  if (required.length > 0) schema.required = required

  return schema
}

function paramsFromSchema(schema: unknown): McpParam[] {
  if (!isPlainObject(schema) || !isPlainObject(schema.properties)) return []

  const required = new Set<string>(
    Array.isArray(schema.required)
      ? schema.required.filter((r): r is string => typeof r === 'string')
      : [],
  )

  const params: McpParam[] = []

  for (const [name, rawProp] of Object.entries(schema.properties)) {
    if (!isPlainObject(rawProp)) continue

    const hasEnum = Array.isArray(rawProp.enum)
    const type: McpParamType = hasEnum
      ? 'enum'
      : ((typeof rawProp.type === 'string' ? rawProp.type : 'string') as McpParamType)

    const param = emptyParam()
    param.name = name
    param.type = MCP_PARAM_TYPES.includes(type) ? type : 'string'
    param.required = required.has(name)
    param.description = typeof rawProp.description === 'string' ? rawProp.description : ''
    param.enumValues = hasEnum
      ? (rawProp.enum as unknown[]).map(v => String(v)).join(', ')
      : ''
    param.format = typeof rawProp.format === 'string' ? rawProp.format : ''
    param.pattern = typeof rawProp.pattern === 'string' ? rawProp.pattern : ''
    param.minimum = typeof rawProp.minimum === 'number' ? String(rawProp.minimum) : ''
    param.maximum = typeof rawProp.maximum === 'number' ? String(rawProp.maximum) : ''

    if (rawProp.default !== undefined) {
      param.default =
        typeof rawProp.default === 'string'
          ? rawProp.default
          : JSON.stringify(rawProp.default)
    }

    params.push(param)
  }

  return params
}

export function emptyForm(): McpManifestForm {
  return {
    exposed: false,
    description: '',
    inputParams: [],
    outputParams: [],
    examples: [],
    requiresConfirmation: false,
    llmFormat: false,
  }
}

export function formFromManifest(value: unknown): McpManifestForm {
  const form = emptyForm()
  if (!isPlainObject(value)) return form

  // Exposure is governed STRICTLY by the `exposed` boolean, mirroring the
  // backend. A missing or non-true flag means NOT exposed — a filled
  // description (or any other content) must never cause a topology to be
  // offered as an MCP tool.
  form.exposed = value.exposed === true
  form.description = typeof value.description === 'string' ? value.description : ''
  form.inputParams = paramsFromSchema(value.input_schema)
  form.outputParams = paramsFromSchema(value.output_schema)
  form.requiresConfirmation = value.requires_confirmation === true
  form.llmFormat = value.llm_format === true

  if (Array.isArray(value.examples)) {
    form.examples = value.examples
      .filter(isPlainObject)
      .map(ex => {
        const when =
          typeof ex.when === 'string'
            ? ex.when
            : typeof ex.description === 'string'
              ? ex.description
              : ''
        const argsSource = ex.args ?? ex.input ?? {}
        const args =
          typeof argsSource === 'string'
            ? argsSource
            : JSON.stringify(argsSource, null, 2)

        return { when, args }
      })
  }

  return form
}

export function manifestFromForm(form: McpManifestForm): Record<string, unknown> {
  const manifest: Record<string, unknown> = { exposed: form.exposed }

  if (form.description.trim() !== '') manifest.description = form.description.trim()

  const inputSchema = buildSchemaFromParams(form.inputParams)
  if (inputSchema) manifest.input_schema = inputSchema

  const outputSchema = buildSchemaFromParams(form.outputParams)
  if (outputSchema) manifest.output_schema = outputSchema

  const examples = form.examples
    .filter(ex => ex.when.trim() !== '' || ex.args.trim() !== '')
    .map(ex => {
      let args: unknown = {}
      const raw = ex.args.trim()
      if (raw !== '') {
        try {
          args = JSON.parse(raw)
        } catch {
          args = raw
        }
      }

      return { when: ex.when.trim(), args }
    })
  if (examples.length > 0) manifest.examples = examples

  if (form.requiresConfirmation) manifest.requires_confirmation = true
  if (form.llmFormat) manifest.llm_format = true

  return manifest
}

export interface DerivedKind {
  kind: 'query' | 'command'
  responseNodeName: string | null
}

interface SchemaNodeLike {
  type?: string
  shape?: string
  label?: string
  name?: string
}

// A topology that ends with a Response node returns data to the caller (query);
// otherwise it just runs (command). Mirrors the backend `deriveTopologyKind`.
export function deriveKindFromNodes(nodes: unknown): DerivedKind {
  if (!Array.isArray(nodes)) return { kind: 'command', responseNodeName: null }

  for (const node of nodes as SchemaNodeLike[]) {
    const isResponse =
      node?.shape === 'terminal' ||
      node?.label === 'Response' ||
      node?.type === 'response'
    if (isResponse) {
      return { kind: 'query', responseNodeName: node?.name || node?.label || 'Response' }
    }
  }

  return { kind: 'command', responseNodeName: null }
}

// Renders the parameters exactly as the LLM will see them in the system prompt
// (name + type + REQUIRED + description), so the author can spot misspecified
// or missing required fields before saving.
export function describeParamsForLlm(params: McpParam[]): string[] {
  return params
    .filter(p => p.name.trim() !== '')
    .map(p => {
      const attrs = [p.type === 'enum' ? 'string (enum)' : p.type]
      if (p.required) attrs.push('REQUIRED')
      const desc = p.description.trim() !== '' ? ` — ${p.description.trim()}` : ''

      return `${p.name.trim()} (${attrs.join(', ')})${desc}`
    })
}
