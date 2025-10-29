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
  ALL_RUN: () => {
    return [null, null, null]
  },
  LAST_1_RUN: () => {
    return [null, null, 1]
  },
  LAST_10_RUN: () => {
    return [null, null, 10]
  },
  LAST_100_RUN: () => {
    return [null, null, 100]
  },
  LAST_1000_RUN: () => {
    return [null, null, 1000]
  },
}
