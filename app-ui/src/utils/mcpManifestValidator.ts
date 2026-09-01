import Ajv from 'ajv'

// Allowed top-level keys of an mcp_description manifest. The Pulse manifest is
// "description-first": a natural-language `description` is the primary field the
// LLM uses to pick the tool. `kind` is no longer authored by hand -- it is
// derived from the topology (presence of a response node). `input_schema` /
// `output_schema` stay optional JSON Schemas. The legacy
// `{kind, input_schema, output_schema}` shape is still accepted for backward
// compatibility.
const ALLOWED_KEYS = [
  'exposed',
  'description',
  'kind',
  'input_schema',
  'output_schema',
  'examples',
  'requires_confirmation',
  'llm_format',
]

const strictAjv = new Ajv({ allErrors: true, strict: true, validateFormats: false })

function validateInnerSchema(field: string, schema: object): string | null {
  try {
    strictAjv.compile(schema)
    return null
  } catch (e) {
    const errors = strictAjv.errors
    if (errors && errors.length > 0) {
      const err = errors[0]
      if (!err) {
        const msg = e instanceof Error ? e.message : 'is not a valid JSON Schema'
        return `${field}: ${msg}`
      }
      const path = (err.instancePath || '').replace(/^\//, '')
      if (err.keyword === 'enum' && err.params?.allowedValues) {
        return `${field}: ${path} must be one of: ${err.params.allowedValues.join(', ')}`
      }
      return `${field}: ${path} ${err.message || 'is invalid'}`
    }
    const msg = e instanceof Error ? e.message : 'is not a valid JSON Schema'
    return `${field}: ${msg}`
  }
}

export function validateMcpManifest(obj: unknown): { valid: boolean; error?: string } {
  if (typeof obj !== 'object' || obj === null || Array.isArray(obj)) {
    return { valid: false, error: 'Manifest must be a JSON object' }
  }

  const manifest = obj as Record<string, unknown>

  for (const key of Object.keys(manifest)) {
    if (!ALLOWED_KEYS.includes(key)) {
      return { valid: false, error: `Unknown field: ${key}` }
    }
  }

  if ('kind' in manifest && manifest.kind !== 'query' && manifest.kind !== 'command') {
    return { valid: false, error: 'kind must be one of: query, command' }
  }

  if ('exposed' in manifest && typeof manifest.exposed !== 'boolean') {
    return { valid: false, error: 'exposed must be of type boolean' }
  }

  if ('description' in manifest && typeof manifest.description !== 'string') {
    return { valid: false, error: 'description must be of type string' }
  }

  const hasDescription =
    typeof manifest.description === 'string' && manifest.description.trim() !== ''

  // Backward compatibility: the legacy manifest had no `description` but always
  // carried the full `{kind, input_schema, output_schema}` triple.
  const hasLegacyTriple =
    'kind' in manifest && 'input_schema' in manifest && 'output_schema' in manifest

  // A `description` is only mandatory once the topology is actually exposed as
  // an MCP tool. A non-exposed (opt-out / draft) manifest may be saved without
  // it so authors don't lose work in progress.
  const isExposed = manifest.exposed === true

  if (isExposed && !hasDescription && !hasLegacyTriple) {
    return { valid: false, error: 'Missing required field: description' }
  }

  if ('input_schema' in manifest) {
    if (typeof manifest.input_schema !== 'object' || manifest.input_schema === null) {
      return { valid: false, error: 'input_schema must be of type object' }
    }
    const inputErr = validateInnerSchema('input_schema', manifest.input_schema as object)
    if (inputErr) return { valid: false, error: inputErr }
  }

  if ('output_schema' in manifest) {
    if (typeof manifest.output_schema !== 'object' || manifest.output_schema === null) {
      return { valid: false, error: 'output_schema must be of type object' }
    }
    const outputErr = validateInnerSchema('output_schema', manifest.output_schema as object)
    if (outputErr) return { valid: false, error: outputErr }
  }

  if ('examples' in manifest && !Array.isArray(manifest.examples)) {
    return { valid: false, error: 'examples must be an array' }
  }

  if ('requires_confirmation' in manifest && typeof manifest.requires_confirmation !== 'boolean') {
    return { valid: false, error: 'requires_confirmation must be of type boolean' }
  }

  if ('llm_format' in manifest && typeof manifest.llm_format !== 'boolean') {
    return { valid: false, error: 'llm_format must be of type boolean' }
  }

  return { valid: true }
}
