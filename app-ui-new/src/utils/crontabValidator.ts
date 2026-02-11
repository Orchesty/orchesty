import * as cron from 'cron-validator'

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

  // Basic description logic (can be enhanced with cronstrue library if needed)
  const parts = expression.trim().split(/\s+/)
  if (parts.length !== 5) {
    return null
  }

  const [minute, hour, day, month, weekday] = parts

  // Simple patterns
  if (expression === '0 0 * * *') return 'Daily at midnight'
  if (expression === '0 2 * * *') return 'Daily at 2:00 AM'
  if (expression === '0 * * * *') return 'Every hour'
  if (expression === '*/15 * * * *') return 'Every 15 minutes'
  if (expression === '0 0 * * 0') return 'Weekly on Sunday at midnight'
  if (expression === '0 0 1 * *') return 'Monthly on the 1st at midnight'

  // Generic description
  return `At ${minute === '*' ? 'every minute' : `minute ${minute}`}, ${hour === '*' ? 'every hour' : `hour ${hour}`}`
}

