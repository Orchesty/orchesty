import { callApi } from "@/utils"
import { UsageStatsTimeBucketHistoryRequest } from "@/api/generated"
import { api } from "@/api"
import { DateTime } from "luxon"

export async function fetchLastBillingHistoryDateGenerated(): Promise<string> {
  const { billingHistoryEnd } =
    await callApi<UsageStatsTimeBucketHistoryRequest>(
      api.timeBucketHistory.data,
      {
        granularity: "monthly",
        timeRangeStart: DateTime.utc(1970, 1).startOf("month").toISO(),
        timeRangeEnd: DateTime.utc(DateTime.now().year, 12)
          .endOf("month")
          .toISO(),
      }
    )

  return billingHistoryEnd
}

export function getTimeRangeStartForApiCall(year?: number, month?: number) {
  if (!year || !month) {
    return DateTime.utc(1970, 1).startOf("month")
  }

  return DateTime.utc(year, month).startOf("month")
}

export function getTimeRangeEndForApiCall(year?: number, month?: number) {
  if (!year || !month) {
    return DateTime.utc(DateTime.now().year, 12)
      .endOf("month")
      .plus({ second: 1 })
  }

  return DateTime.utc(year, month).endOf("month").plus({ second: 1 })
}
