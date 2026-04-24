import api from './api' // Import the configured axios instance
import type {
  WorkerGroup,
  ApplicationInstall,
  ApplicationQueryParams,
  ApplicationWithStatus,
  ApplicationStatus,
  ApplicationSetting,
  ApplicationInstallApiResponse,
} from '@/types/applications';
import mockData from '@/assets/mock-data/applications-data.json';

// Simulate API delay
const delay = (ms: number) => new Promise(resolve => setTimeout(resolve, ms));

// API types for /api/applications response
interface ApplicationApiResponse {
  activated: boolean
  authorized: boolean
  description: string
  installable: boolean
  installed: boolean
  key: string
  logo: string
  name: string
}

interface WorkerApiResponse {
  applications: ApplicationApiResponse[]
  name: string
  url: string
}

// Helper to determine status from API fields
function mapApiStatusToComponentStatus(
  installed: boolean,
  authorized: boolean,
  activated: boolean
): ApplicationStatus {
  if (!installed) return 'available'
  if (installed && !authorized) return 'installed'
  if (installed && authorized && !activated) return 'authorized'
  return 'activated' // all three true
}

// Helper to map API application to component format
function mapApiApplicationToComponent(
  apiApp: ApplicationApiResponse,
  workerName: string
): ApplicationWithStatus {
  return {
    key: apiApp.key,
    name: apiApp.name,
    description: apiApp.description,
    // Default values for fields not provided by API
    application_type: 'webhook', // Default, can be refined if API provides this later
    authorization_type: 'oauth2', // Default, can be refined if API provides this later
    status: mapApiStatusToComponentStatus(apiApp.installed, apiApp.authorized, apiApp.activated),
    authorized: apiApp.authorized,
    worker: workerName,
    logo: apiApp.logo, // Base64 encoded SVG
  }
}

/**
 * Fetch all applications grouped by workers
 */
export async function fetchApplications(params?: ApplicationQueryParams): Promise<WorkerGroup[]> {
  const response = await api.get<WorkerApiResponse[]>('/api/applications')

  let workers: WorkerGroup[] = response.data.map((workerApi) => ({
    name: workerApi.name,
    applications: workerApi.applications.map((app) =>
      mapApiApplicationToComponent(app, workerApi.name)
    ),
  }))

  // Apply filters
  if (params?.status) {
    workers = workers
      .map((worker) => ({
        ...worker,
        applications: worker.applications.filter((app) => {
          if (params.status === 'all-installed') {
            // Show all installed apps (unauthorized, authorized, and activated)
            return app.status === 'installed' || app.status === 'authorized' || app.status === 'activated';
          }
          return app.status === params.status;
        }),
      }))
      .filter((worker) => worker.applications.length > 0)
  }

  if (params?.worker) {
    workers = workers.filter((worker) => worker.name === params.worker)
  }

  if (params?.search) {
    const searchLower = params.search.toLowerCase()
    workers = workers
      .map((worker) => ({
        ...worker,
        applications: worker.applications.filter(
          (app) =>
            app.name.toLowerCase().includes(searchLower) ||
            app.description.toLowerCase().includes(searchLower)
        ),
      }))
      .filter((worker) => worker.applications.length > 0)
  }

  return workers
}

// Helper functions for API response transformation

/**
 * Normalize choices format (API returns objects, component expects strings)
 */
function normalizeChoices(choices: Array<Record<string, string>> | string[]): string[] {
  if (!choices || choices.length === 0) return []

  // If already string array, return as-is
  if (typeof choices[0] === 'string') return choices as string[]

  // Convert object array to string array (use keys)
  return (choices as Array<Record<string, string>>)
    .map((choice) => Object.keys(choice)[0])
    .filter((key): key is string => Boolean(key))
}

/**
 * Transform API response to component format
 */
function mapApiInstallToComponent(
  apiResponse: ApplicationInstallApiResponse,
  workerName: string
): ApplicationInstall {
  // Flatten nested form structure to flat array of settings
  const flatSettings: ApplicationSetting[] = []

  // Check if applicationSettings exists and is not null
  if (apiResponse.applicationSettings && typeof apiResponse.applicationSettings === 'object') {
    Object.entries(apiResponse.applicationSettings).forEach(([formKey, formGroup]) => {
      if (formGroup && formGroup.fields && Array.isArray(formGroup.fields)) {
        formGroup.fields.forEach((field) => {
          flatSettings.push({
            key: field.key,
            type: field.type,
            label: field.label,
            value: field.value?.toString() || '',
            description: field.description,
            required: field.required,
            readOnly: field.readOnly,
            disabled: field.disabled,
            choices: normalizeChoices(field.choices),
            tab: formGroup.publicName, // For UI display: "Authorization"
            formKey: formKey, // For API grouping: "authorization_form"
          })
        })
      }
    })
  }

  return {
    key: apiResponse.key,
    name: apiResponse.name,
    description: apiResponse.description,
    application_type: apiResponse.application_type,
    authorization_type: apiResponse.authorization_type,
    authorized: apiResponse.authorized ?? false,
    applicationSettings: flatSettings,
    worker: workerName,
    logo: apiResponse.logo,
    info: apiResponse.info || undefined,
  }
}

/**
 * Fetch application install details with settings
 */
export async function fetchApplicationInstall(
  key: string,
  worker: string,
  isInstalled: boolean = false
): Promise<ApplicationInstall> {
  const endpoint = isInstalled
    ? `/api/applications/${key}`           // For installed apps
    : `/api/applications/${key}/preview`   // For uninstalled apps

  const response = await api.get<ApplicationInstallApiResponse>(
    endpoint,
    { params: { sdk: worker } }
  )

  return mapApiInstallToComponent(response.data, worker)
}

/**
 * Install an application
 */
export async function installApplication(key: string, worker: string): Promise<ApplicationInstall> {
  const response = await api.post<ApplicationInstallApiResponse>(
    `/api/applications/${key}`,
    {},
    { params: { sdk: worker } }
  )
  const install = mapApiInstallToComponent(response.data, worker)
  install.authorized = true // Mark as installed
  return install
}

/**
 * Uninstall an application
 */
export async function uninstallApplication(key: string, worker: string): Promise<void> {
  await api.delete(`/api/applications/${key}`, {
    params: { sdk: worker },
  })
}

/**
 * Change application state (activate/deactivate)
 */
export async function changeApplicationState(key: string, worker: string, enabled: boolean): Promise<void> {
  await api.put(
    `/api/applications/${key}/changeState`,
    { enabled },
    { params: { sdk: worker } }
  )
}

/**
 * Update application settings
 */
export async function updateApplicationSettings(
  key: string,
  worker: string,
  formValues: Record<string, unknown>,
  applicationSettings: ApplicationSetting[]
): Promise<ApplicationInstall> {
  // Transform flat formValues into grouped structure by formKey
  const groupedValues: Record<string, Record<string, unknown>> = {}

  applicationSettings.forEach(setting => {
    const groupKey = setting.formKey || setting.tab || 'general'
    if (!groupedValues[groupKey]) {
      groupedValues[groupKey] = {}
    }
    groupedValues[groupKey][setting.key] = formValues[setting.key]
  })

  // Make PUT request with grouped data
  const response = await api.put<ApplicationInstallApiResponse>(
    `/api/applications/${key}`,
    groupedValues,
    { params: { sdk: worker } }
  )

  return mapApiInstallToComponent(response.data, worker)
}

/**
 * Authorize an application (trigger OAuth flow).
 * Opens the authorize endpoint directly in a new browser window.
 * The backend redirects to the OAuth provider.
 */
export function authorizeApplication(key: string, worker: string): void {
  const baseUrl = api.defaults.baseURL || window.location.origin
  const authorizeURL = new URL(`/api/applications/${key}/authorize`, baseUrl)
  authorizeURL.searchParams.append('sdk', worker)
  authorizeURL.searchParams.append('redirect_url', `${window.location.href}?sdk=${worker}`)

  const token = localStorage.getItem('auth_token')
  if (token) {
    authorizeURL.searchParams.append('Authorization', token)
  }

  window.location.href = authorizeURL.href
}

export interface InstalledApplicationWithSyncMethods {
  key: string
  name: string
  worker: string
  logo: string
  authorized: boolean
  syncMethods: string[]
}

/**
 * Normalize the `/sync/list` payload into a clean `string[]`.
 *
 * The PHP gateway's `doRequest` always merges a `host` key into responses,
 * which mangles a list-shaped SDK response (`["trace"]`) into an
 * object-with-numeric-keys (`{"0":"trace","host":"…"}`). Handle both shapes
 * so the FE works regardless of whether the backend fix is deployed.
 */
function normalizeSyncMethods(raw: unknown): string[] {
  if (Array.isArray(raw)) {
    return raw.filter((v): v is string => typeof v === 'string')
  }
  if (raw && typeof raw === 'object') {
    return Object.entries(raw as Record<string, unknown>)
      .filter(([key, value]) => /^\d+$/.test(key) && typeof value === 'string')
      .map(([, value]) => value as string)
  }
  return []
}

/**
 * Fetch all *installed* applications across all workers, including their sync method names.
 * Sync method names are flattened from `sync<Method>(...)` to `<method>` by the SDK
 * (e.g. an app implementing `syncTrace()` exposes `'trace'` here).
 *
 * Used e.g. when picking the application that backs a platform service (Settings → Trace).
 */
export async function fetchInstalledApplicationsWithSyncMethods(): Promise<
  InstalledApplicationWithSyncMethods[]
> {
  const response = await api.get<WorkerApiResponse[]>('/api/applications')

  const tasks: Array<Promise<InstalledApplicationWithSyncMethods>> = []

  for (const worker of response.data ?? []) {
    for (const app of worker.applications ?? []) {
      if (!app.installed) continue

      const baseInfo = {
        key: app.key,
        name: app.name,
        worker: worker.name,
        logo: app.logo,
        authorized: app.authorized,
      }

      tasks.push(
        api
          .get<unknown>(
            `/api/applications/${encodeURIComponent(app.key)}/sync/list`,
            { params: { sdk: worker.name } },
          )
          .then<InstalledApplicationWithSyncMethods>((res) => ({
            ...baseInfo,
            syncMethods: normalizeSyncMethods(res.data),
          }))
          .catch((error: unknown) => {
            console.warn(
              `[applicationsService] Failed to load sync methods for ${app.key}@${worker.name}:`,
              error,
            )
            return { ...baseInfo, syncMethods: [] }
          }),
      )
    }
  }

  return Promise.all(tasks)
}

/**
 * Fetch available worker names
 */
export async function fetchWorkerNames(): Promise<string[]> {
  await delay(200);

  return mockData.workers.map(worker => worker.name);
}

/**
 * Fetch available application names
 */
export async function fetchApplicationNames(): Promise<string[]> {
  await delay(200);

  const allApps = mockData.workers.flatMap(worker => worker.applications);
  return [...new Set(allApps.map(app => app.name))].sort();
}
