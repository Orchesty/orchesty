import CronExpressionParser from 'cron-parser'
import cronstrue from 'cronstrue'

export interface CrontabValidationResult {
  valid: boolean
  error?: string
}

/**
 * Validates a crontab expression by attempting to parse it with cron-parser.
 * This ensures validation and next-run computation use the same library.
 */
export function validateCrontab(expression: string): CrontabValidationResult {
  if (!expression || expression.trim() === '') {
    return {
      valid: false,
      error: 'Crontab expression is required',
    }
  }

  try {
    CronExpressionParser.parse(expression.trim())
    return { valid: true }
  } catch {
    return {
      valid: false,
      error: 'Invalid crontab expression. Format: minute hour day month weekday (e.g., 0 2 * * *)',
    }
  }
}

/**
 * Gets a human-readable description of a crontab expression
 */
export function getCrontabDescription(expression: string): string | null {
  if (!validateCrontab(expression).valid) {
    return null
  }

  try {
    return cronstrue.toString(expression.trim(), { use24HourTimeFormat: true })
  } catch {
    return null
  }
}
