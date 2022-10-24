import moment from "moment"
import { i18n } from "@/localization"

export const toLocalDateTime = (datetime) => {
  return moment(datetime).locale(i18n.locale).format("lll")
}

export const toLocalDate = (datetime) => {
  return moment(datetime).locale(i18n.locale).format("L")
}

export const toLocalTime = (datetime) => {
  return moment(datetime).locale(i18n.locale).format("LT")
}

export const internationalFormat = (date) => {
  return moment(date)
    .add(moment().utc().utcOffset(), "minutes")
    .format("DD.MM.YYYY HH:mm:ss ")
}

export const internationalFormatTimestamp = (date) => {
  return moment
    .unix(date)
    .add(moment().utc().utcOffset())
    .format("DD.MM.YYYY HH:mm:ss ")
}

export const timeAgo = (datetime) => {
  return moment(datetime).locale(i18n.locale).fromNow()
}

export const getInitials = (fullname) => {
  const data = fullname.replace(/ +/g, " ").split(" ", 2)

  const firstChar = data[0] ? data[0].substr(0, 1).toUpperCase() : ""

  const secondChar = data[1] ? data[1].substr(0, 1).toUpperCase() : ""

  return `${firstChar}${secondChar}`
}
