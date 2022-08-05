import { LOCALE } from '@/localization'

export const toLocalDateTime = (datetime) => {
  if (datetime === null || datetime.length === 0) return ''

  return Intl.DateTimeFormat(LOCALE).format(new Date(datetime))
}

export const toLocalDate = (datetime) => {
  if (datetime === null || datetime.length === 0) return ''

  const options = {
    day: 'numeric',
    month: 'numeric',
    year: 'numeric',
  }
  return Intl.DateTimeFormat(LOCALE, options).format(new Date(datetime))
}

export const toMonthYear = (datetime) => {
  if (datetime === null || datetime.length === 0) return ''

  const options = {
    month: 'long',
    year: 'numeric',
  }
  return Intl.DateTimeFormat(LOCALE, options).format(new Date(datetime))
}

export const toLocalTime = (datetime) => {
  if (datetime === null || datetime.length === 0) return ''

  const options = {
    hour: 'numeric',
    minute: 'numeric',
  }
  return Intl.DateTimeFormat(LOCALE, options).format(new Date(datetime))
}
