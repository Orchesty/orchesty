import type {
  ProcessesChartData,
  LimiterData,
  TrashData,
  ProcessFilter,
  TimeFilter,
} from '@/types/dashboard'
import processesChartDataJson from '@/assets/mock-data/processes-chart-data.json'
import limiterDataJson from '@/assets/mock-data/limiter-data.json'
import dashboardTrashDataJson from '@/assets/mock-data/dashboard-trash-data.json'

/**
 * Get processes chart data
 * Currently returns mock data, will be replaced with API call
 * @param filter - 'all' or 'failed'
 * @param timeFilter - time range filter (not yet implemented in mock)
 */
export async function fetchProcessesData(
  filter: ProcessFilter = 'all',
  // eslint-disable-next-line @typescript-eslint/no-unused-vars
  _timeFilter: TimeFilter = '7d',
): Promise<ProcessesChartData> {
  // Simulate API delay
  await new Promise((resolve) => setTimeout(resolve, 50))
  
  const data = processesChartDataJson as ProcessesChartData
  const FAILED_OFFSET = 1000
  
  // Apply rendering logic: failed has priority (use offset for visual separation)
  const processedData = {
    ...data,
    series: data.series.map((s) => ({
      ...s,
      data: s.data.map((d) => {
        let displayValue = 0
        
        // Failed has priority: if any failed, use failed value with offset
        if (d.meta.failed > 0) {
          displayValue = d.meta.failed + FAILED_OFFSET
        } else {
          displayValue = d.meta.success
        }
        
        return {
          ...d,
          y: displayValue,
          meta: {
            ...d.meta,
            isFailed: d.meta.failed > 0
          }
        }
      }),
    })),
  }
  
  // Apply filter if 'failed'
  if (filter === 'failed') {
    return {
      ...processedData,
      series: processedData.series.map((s) => ({
        ...s,
        data: s.data.map((d) => {
          // If filtering by 'failed' and no failures, set to 0 but keep the position
          if (d.meta.failed === 0) {
            return {
              ...d,
              y: 0,
              meta: { success: 0, failed: 0, isFailed: false }
            }
          }
          return d
        }),
      })),
    }
  }
  
  return processedData
}

/**
 * Get limiter card data
 * Currently returns static mock data from JSON
 */
export async function fetchLimiterData(
  // eslint-disable-next-line @typescript-eslint/no-unused-vars
  _timeFilter: TimeFilter = '7d',
): Promise<LimiterData> {
  // Simulate API delay
  await new Promise((resolve) => setTimeout(resolve, 50))
  
  return limiterDataJson as LimiterData
}

/**
 * Get trash card data
 * Currently returns static mock data from JSON
 */
export async function fetchTrashData(
  // eslint-disable-next-line @typescript-eslint/no-unused-vars
  _timeFilter: TimeFilter = '7d',
): Promise<TrashData> {
  // Simulate API delay
  await new Promise((resolve) => setTimeout(resolve, 50))
  
  return dashboardTrashDataJson as TrashData
}

