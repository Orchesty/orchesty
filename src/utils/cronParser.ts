import CronExpressionParser from 'cron-parser'

/**
 * Calculate the next run time for a crontab expression.
 * @param expression - Standard 5-field crontab expression (e.g. "0 2 * * *")
 * @param fromDate - Calculate next run relative to this date (defaults to now)
 * @returns Date of the next run, or null if the expression is invalid
 */
export function getNextCronRun(expression: string, fromDate?: Date): Date | null {
  if (!expression || expression.trim() === '') {
    return null
  }

  try {
    const cron = CronExpressionParser.parse(expression.trim(), {
      currentDate: fromDate || new Date(),
    })
    return cron.next().toDate()
  } catch {
    return null
  }
}

/**
 * Calculate the next N run times for a crontab expression.
 * @param expression - Standard 5-field crontab expression
 * @param count - Number of upcoming runs to return (default: 2)
 * @param fromDate - Calculate relative to this date (defaults to now)
 * @returns Array of Dates, or empty array if the expression is invalid
 */
export function getNextCronRuns(expression: string, count = 2, fromDate?: Date): Date[] {
  if (!expression || expression.trim() === '') {
    return []
  }

  try {
    const cron = CronExpressionParser.parse(expression.trim(), {
      currentDate: fromDate || new Date(),
    })
    return cron.take(count).map(d => d.toDate())
  } catch {
    return []
  }
}

/**
 * Format a Date as a human-readable "next run" string.
 * Returns relative label for today/tomorrow, otherwise a short date+time.
 * @param date - The date to format
 * @returns Formatted string like "Today 14:30", "Tomorrow 02:00", or "25 Feb 14:30"
 */
export function formatNextRun(date: Date): string {
  const now = new Date()
  const today = new Date(now.getFullYear(), now.getMonth(), now.getDate())
  const target = new Date(date.getFullYear(), date.getMonth(), date.getDate())
  const diffDays = Math.round((target.getTime() - today.getTime()) / (1000 * 60 * 60 * 24))

  const time = `${String(date.getHours()).padStart(2, '0')}:${String(date.getMinutes()).padStart(2, '0')}`

  if (diffDays === 0) return `Today ${time}`
  if (diffDays === 1) return `Tomorrow ${time}`

  const day = date.getDate()
  const month = date.toLocaleString('en-US', { month: 'short' })
  return `${day} ${month} ${time}`
}
