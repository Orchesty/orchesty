import moment from "moment"

export const QUICK_FILTERS = {
  LAST_5_MINS: () => {
    return [
      moment().utc().subtract(5, "minutes").format(),
      moment().utc().format(),
    ]
  },
  LAST_30_MINS: () => {
    return [
      moment().utc().subtract(30, "minutes").format(),
      moment().utc().format(),
    ]
  },
  LAST_HOUR: () => {
    return [
      moment().utc().subtract(1, "hour").format(),
      moment().utc().format(),
    ]
  },
  LAST_6_HOURS: () => {
    return [
      moment().utc().subtract(6, "hour").format(),
      moment().utc().format(),
    ]
  },
  LAST_24_HOURS: () => {
    return [moment().utc().subtract(1, "day").format(), moment().utc().format()]
  },
}
