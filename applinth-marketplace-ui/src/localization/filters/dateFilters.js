import { LOCALE } from "@/localization"

export const toLocalDateTime = (datetime) => {
  if (datetime === null || datetime.length === 0) return ""

  const options = {
    year: "numeric",
    month: "numeric",
    day: "numeric",
    hour: "numeric",
    minute: "numeric",
    second: "numeric",
    hour12: false,
  }

  return new Intl.DateTimeFormat(LOCALE, options).format(new Date(datetime))
}

export const toLocalDate = (datetime) => {
  if (datetime === null || datetime.length === 0) return ""

  const options = {
    year: "numeric",
    month: "numeric",
    day: "numeric",
  }

  return new Intl.DateTimeFormat(LOCALE, options).format(new Date(datetime))
}

export const toMonthYear = (datetime) => {
  if (datetime === null || datetime.length === 0) return ""

  const options = {
    month: "long",
    year: "numeric",
  }
  return new Intl.DateTimeFormat(LOCALE, options).format(new Date(datetime))
}

export const toLocalTime = (datetime) => {
  if (datetime === null || datetime.length === 0) return ""

  const options = {
    hour: "numeric",
    minute: "numeric",
  }
  return new Intl.DateTimeFormat(LOCALE, options).format(new Date(datetime))
}
