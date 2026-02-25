import * as cron from 'cron-validator'
import cronstrue from 'cronstrue'

export interface CrontabValidationResult {
  valid: boolean
  error?: string
}

/**
 * Validates a crontab expression
 * @param expression - The crontab expression to validate (e.g., "0 2 * * *")
 * @returns Validation result with error message if invalid
 */
export function validateCrontab(expression: string): CrontabValidationResult {
  if (!expression || expression.trim() === '') {
    return {
      valid: false,
      error: 'Crontab expression is required',
    }
  }

  const trimmedExpression = expression.trim()

  // Use cron-validator library
  const isValid = cron.isValidCron(trimmedExpression, {
    seconds: false, // Standard cron format (5 fields)
    alias: true, // Allow aliases like @daily, @hourly
    allowBlankDay: true, // Allow ? in day fields
  })

  if (!isValid) {
    return {
      valid: false,
      error: 'Invalid crontab expression. Format: minute hour day month weekday (e.g., 0 2 * * *)',
    }
  }

  return {
    valid: true,
  }
}

/**
 * Gets a human-readable description of a crontab expression
 * @param expression - The crontab expression
 * @returns Human-readable description or null if invalid
 */
export function getCrontabDescription(expression: string): string | null {
  const validation = validateCrontab(expression)
  if (!validation.valid) {
    return null
  }

  try {
    return cronstrue.toString(expression.trim(), { use24HourTimeFormat: true })
  } catch {
    return null
  }
}

