import applicationsData from '@/assets/mock-data/applications-data.json'

export interface Application {
  id: string
  name: string
  type: string
  status: string
}

/**
 * Fetch all applications
 * Currently returns mock data, will be replaced with API call
 */
export async function fetchApplications(): Promise<Application[]> {
  // Simulate API delay
  await new Promise((resolve) => setTimeout(resolve, 100))

  // FOR DEVELOPMENT: Return mock data
  // In production: return axios.get('/api/applications')
  return applicationsData.data as Application[]
}

/**
 * Get application names for dropdown filter
 * Returns array with "All Applications" as first option
 */
export async function fetchApplicationNames(): Promise<string[]> {
  const applications = await fetchApplications()
  return applications.map((app) => app.name)
}

