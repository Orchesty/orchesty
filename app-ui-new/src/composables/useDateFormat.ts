import { ref, computed } from 'vue'

export type DateFormatType = 'eu' | 'us'

const STORAGE_KEY = 'orchesty_date_format'

// Shared reactive state (singleton across all composable calls)
const dateFormat = ref<DateFormatType>(
  (localStorage.getItem(STORAGE_KEY) as DateFormatType) || 'eu'
)

const locale = computed(() => (dateFormat.value === 'eu' ? 'de-DE' : 'en-US'))

/**
 * Set the date format preference and persist to localStorage
 */
function setDateFormat(format: DateFormatType) {
  dateFormat.value = format
  localStorage.setItem(STORAGE_KEY, format)
}

/**
 * Format date only (e.g. "09.02.2026" / "02/09/2026")
 */
function formatDate(input: string | Date | null | undefined): string {
  if (!input) return ''
  const date = input instanceof Date ? input : new Date(input)
  if (isNaN(date.getTime())) return String(input)
  return date.toLocaleDateString(locale.value, {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  })
}

/**
 * Format date + time (e.g. "09.02.2026 14:30:05" / "02/09/2026 2:30:05 PM")
 */
function formatDateTime(input: string | Date | null | undefined): string {
  if (!input) return ''
  const date = input instanceof Date ? input : new Date(input)
  if (isNaN(date.getTime())) return String(input)

  const datePart = date.toLocaleDateString(locale.value, {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  })
  const timePart = date.toLocaleTimeString(locale.value, {
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
  })
  return `${datePart} ${timePart}`
}

/**
 * Format date + time without seconds (e.g. "09.02.2026 14:30" / "02/09/2026 2:30 PM")
 */
function formatDateTimeShort(input: string | Date | null | undefined): string {
  if (!input) return ''
  const date = input instanceof Date ? input : new Date(input)
  if (isNaN(date.getTime())) return String(input)

  const datePart = date.toLocaleDateString(locale.value, {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  })
  const timePart = date.toLocaleTimeString(locale.value, {
    hour: '2-digit',
    minute: '2-digit',
  })
  return `${datePart} ${timePart}`
}

/**
 * Format time only (e.g. "14:30:05" / "2:30:05 PM")
 */
function formatTime(input: string | Date | null | undefined): string {
  if (!input) return ''
  const date = input instanceof Date ? input : new Date(input)
  if (isNaN(date.getTime())) return String(input)
  return date.toLocaleTimeString(locale.value, {
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
  })
}

/**
 * Short date for chart axes (e.g. "9. 2." / "Feb 9")
 */
function formatChartDate(input: string | Date | null | undefined): string {
  if (!input) return ''
  const date = input instanceof Date ? input : new Date(input)
  if (isNaN(date.getTime())) return String(input)

  if (dateFormat.value === 'eu') {
    return `${date.getDate()}. ${date.getMonth() + 1}.`
  }
  return date.toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
  })
}

/**
 * Granularity-aware chart label formatting.
 * - granularity <= 120 min (1h, 24h filters): show only time "H:mm"
 * - granularity <= 720 min (7d filter): show "d.m. H:mm"
 * - granularity > 720 min (30d filter): show only date "d.m."
 */
function formatChartLabel(input: string | Date | null | undefined, granularityMinutes: number): string {
  if (!input) return ''
  const date = input instanceof Date ? input : new Date(input)
  if (isNaN(date.getTime())) return String(input)

  const day = date.getDate()
  const month = date.getMonth() + 1
  const hours = date.getHours()
  const minutes = String(date.getMinutes()).padStart(2, '0')

  if (dateFormat.value === 'eu') {
    if (granularityMinutes <= 120) {
      return `${hours}:${minutes}`
    }
    if (granularityMinutes <= 720) {
      return `${day}.${month}. ${hours}:${minutes}`
    }
    return `${day}.${month}.`
  }

  // US format
  if (granularityMinutes <= 120) {
    return date.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' })
  }
  if (granularityMinutes <= 720) {
    const shortDate = date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })
    const shortTime = date.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' })
    return `${shortDate} ${shortTime}`
  }
  return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })
}

export function useDateFormat() {
  return {
    dateFormat,
    locale,
    setDateFormat,
    formatDate,
    formatDateTime,
    formatDateTimeShort,
    formatTime,
    formatChartDate,
    formatChartLabel,
  }
}
