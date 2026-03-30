import Ajv from 'ajv'

const ajv = new Ajv({ allErrors: true })

const manifestSchema = {
  type: 'object',
  required: ['kind', 'input_schema', 'output_schema'],
  additionalProperties: false,
  properties: {
    kind: { enum: ['query', 'command'] },
    input_schema: { type: 'object' },
    output_schema: { type: 'object' },
  },
}

const validateOuter = ajv.compile(manifestSchema)

function formatAjvErrors(errors: typeof validateOuter.errors): string {
  if (!errors || errors.length === 0) return 'Validation failed'

  const err = errors[0]
  if (!err) return 'Validation failed'

  const path = err.instancePath ? err.instancePath.replace(/^\//, '') : ''

  if (err.keyword === 'required') {
    return `Missing required field: ${err.params?.missingProperty ?? ''}`
  }
  if (err.keyword === 'enum') {
    return `${path || 'Field'} must be one of: ${err.params?.allowedValues?.join(', ') ?? ''}`
  }
  if (err.keyword === 'type') {
    return `${path || 'Field'} must be of type ${err.params?.type ?? ''}`
  }
  if (err.keyword === 'additionalProperties') {
    return `Unknown field: ${err.params?.additionalProperty ?? ''}`
  }

  return err.message || 'Validation failed'
}

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

  const outerValid = validateOuter(obj)
  if (!outerValid) {
    return { valid: false, error: formatAjvErrors(validateOuter.errors) }
  }

  const manifest = obj as { input_schema: object; output_schema: object }

  const inputErr = validateInnerSchema('input_schema', manifest.input_schema)
  if (inputErr) return { valid: false, error: inputErr }

  const outputErr = validateInnerSchema('output_schema', manifest.output_schema)
  if (outputErr) return { valid: false, error: outputErr }

  return { valid: true }
}
