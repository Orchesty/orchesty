/**
 * Convert global time filter value to datetime range for local filters
 * Handles both predefined ranges and custom date ranges
 * All ranges work backwards from NOW (e.g., "1h" = from now-1h to now)
 */
export function convertTimeFilterToDateTimeRange(timeFilter: string): {
  from: string
  to: string
} {
  const now = new Date()
  let fromDate: Date
  let toDate: Date = new Date(now) // Always "to" is NOW

  // Handle custom range format: "custom:YYYY-MM-DD:YYYY-MM-DD"
  if (timeFilter.startsWith('custom:')) {
    const parts = timeFilter.split(':')
    if (parts.length === 3 && parts[1] && parts[2]) {
      // Custom range: set time to start of day for "from" and end of day for "to"
      fromDate = new Date(parts[1])
      fromDate.setHours(0, 0, 0, 0)

      toDate = new Date(parts[2])
      toDate.setHours(23, 59, 59, 999)

      return {
        from: formatDateTimeLocal(fromDate),
        to: formatDateTimeLocal(toDate),
      }
    }
  }

  // Handle short format: "1h", "24h", "7d", "30d", etc.
  const shortFormatMatch = timeFilter.match(/^(\d+)(h|d)$/)
  if (shortFormatMatch) {
    const value = parseInt(shortFormatMatch[1])
    const unit = shortFormatMatch[2]

    fromDate = new Date(now)

    if (unit === 'h') {
      // Hours: subtract hours from now
      fromDate.setHours(now.getHours() - value)
    } else if (unit === 'd') {
      // Days: subtract days from now
      fromDate.setDate(now.getDate() - value)
    }

    return {
      from: formatDateTimeLocal(fromDate),
      to: formatDateTimeLocal(toDate),
    }
  }

  // Handle predefined ranges
  switch (timeFilter) {
    case 'yesterday':
      fromDate = new Date(now)
      fromDate.setDate(now.getDate() - 1)
      fromDate.setHours(0, 0, 0, 0)

      toDate = new Date(now)
      toDate.setDate(now.getDate() - 1)
      toDate.setHours(23, 59, 59, 999)
      break

    case 'today':
      // From start of today to NOW
      fromDate = new Date(now)
      fromDate.setHours(0, 0, 0, 0)

      toDate = new Date(now)
      break

    case 'last-7-days':
      fromDate = new Date(now)
      fromDate.setDate(now.getDate() - 7)
      break

    case 'last-30-days':
      fromDate = new Date(now)
      fromDate.setDate(now.getDate() - 30)
      break

    case 'last-90-days':
      fromDate = new Date(now)
      fromDate.setDate(now.getDate() - 90)
      break

    case 'this-month':
      // From start of this month to NOW
      fromDate = new Date(now.getFullYear(), now.getMonth(), 1)
      fromDate.setHours(0, 0, 0, 0)

      toDate = new Date(now)
      break

    default:
      // Default to last 7 days if unknown filter
      fromDate = new Date(now)
      fromDate.setDate(now.getDate() - 7)
  }

  return {
    from: formatDateTimeLocal(fromDate),
    to: formatDateTimeLocal(toDate),
  }
}

/**
 * Format Date object to datetime-local input format: YYYY-MM-DDTHH:mm
 */
function formatDateTimeLocal(date: Date): string {
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  const hours = String(date.getHours()).padStart(2, '0')
  const minutes = String(date.getMinutes()).padStart(2, '0')

  return `${year}-${month}-${day}T${hours}:${minutes}`
}

/**
 * Convert datetime-local format to ISO string for API
 */
export function formatDateTimeForApi(dateTimeLocal: string | null): string | null {
  if (!dateTimeLocal) return null

  // datetime-local format is already close to ISO, just need to add seconds
  const date = new Date(dateTimeLocal)
  return date.toISOString()
}

/**
 * Convert datetime-local format to ISO 8601 string for API filters
 */
export function formatDateTimeForApiFilter(dateTimeLocal: string): string {
  const date = new Date(dateTimeLocal)
  return date.toISOString()
}

/**
 * Format ISO date string for chart display
 * Example: "2025-12-31T23:36:00Z" -> "Dec 31, 23:36"
 */
export function formatChartDate(isoDate: string): string {
  const date = new Date(isoDate)
  return date.toLocaleString('en-US', {
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

/**
 * Format ISO date string for display with full date and time
 * Example: "2025-12-31T23:36:00Z" -> "Dec 31, 2025, 11:36 PM"
 */
export function formatDateTime(isoDate: string): string {
  const date = new Date(isoDate)
  return date.toLocaleString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

/**
 * Calculate time range from a heatmap time slot
 * Creates a ±30 minute window (1 hour total) around the clicked time slot
 *
 * @param timeSlot - Time slot in format "YYYY-MM-DD HH:mm" or similar
 * @returns Object with from and to datetime strings in format YYYY-MM-DDTHH:mm
 */
export function calculateTimeRangeFromSlot(timeSlot: string): { from: string; to: string } {
  // Parse the time slot - handle various formats
  const slotDate = new Date(timeSlot)

  // Check if date is valid
  if (isNaN(slotDate.getTime())) {
    console.error('Invalid time slot:', timeSlot)
    // Return current hour as fallback
    const now = new Date()
    return {
      from: formatDateTimeLocal(new Date(now.getTime() - 30 * 60 * 1000)),
      to: formatDateTimeLocal(new Date(now.getTime() + 30 * 60 * 1000)),
    }
  }

  // Create ±30 minute window (1 hour total)
  const from = new Date(slotDate)
  from.setMinutes(from.getMinutes() - 30)

  const to = new Date(slotDate)
  to.setMinutes(to.getMinutes() + 30)

  return {
    from: formatDateTimeLocal(from),
    to: formatDateTimeLocal(to),
  }
}

/**
 * Convert time filter to date range with multiplier
 * @param timeFilter - The time filter (1h, 24h, 7d, 30d)
 * @param multiplier - Multiply the time range (default 1)
 * @returns Object with from and to ISO date strings
 */
export function convertTimeFilterToDateTimeRangeWithMultiplier(
  timeFilter: string,
  multiplier: number = 1
): { from: string; to: string } {
  const now = new Date()
  const to = now.toISOString()

  let hoursAgo = 0
  switch (timeFilter) {
    case '1h':
      hoursAgo = 1 * multiplier
      break
    case '24h':
      hoursAgo = 24 * multiplier
      break
    case '7d':
      hoursAgo = 24 * 7 * multiplier
      break
    case '30d':
      hoursAgo = 24 * 30 * multiplier
      break
  }

  const from = new Date(now.getTime() - hoursAgo * 60 * 60 * 1000)

  return {
    from: from.toISOString(),
    to
  }
}
