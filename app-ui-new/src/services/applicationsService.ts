import type {
  WorkerGroup,
  ApplicationInstall,
  ApplicationQueryParams,
} from '@/types/applications';
import mockData from '@/assets/mock-data/applications-data.json';

// Simulate API delay
const delay = (ms: number) => new Promise(resolve => setTimeout(resolve, ms));

/**
 * Fetch all applications grouped by workers
 */
export async function fetchApplications(params?: ApplicationQueryParams): Promise<WorkerGroup[]> {
  await delay(300);

  let workers = JSON.parse(JSON.stringify(mockData.workers)) as WorkerGroup[];

  // Apply filters
  if (params?.status) {
    workers = workers.map(worker => ({
      ...worker,
      applications: worker.applications.filter(app => {
        if (params.status === 'installed') {
          return app.status === 'unauthorized' || app.status === 'authorized';
        }
        return app.status === params.status;
      }),
    })).filter(worker => worker.applications.length > 0);
  }

  if (params?.worker) {
    workers = workers.filter(worker => worker.name === params.worker);
  }

  if (params?.search) {
    const searchLower = params.search.toLowerCase();
    workers = workers.map(worker => ({
      ...worker,
      applications: worker.applications.filter(app =>
        app.name.toLowerCase().includes(searchLower) ||
        app.description.toLowerCase().includes(searchLower)
      ),
    })).filter(worker => worker.applications.length > 0);
  }

  return workers;
}

/**
 * Fetch application install details with settings
 */
export async function fetchApplicationInstall(key: string, user: string): Promise<ApplicationInstall> {
  await delay(400);

  const installData = mockData.applicationInstalls[key as keyof typeof mockData.applicationInstalls];
  
  if (!installData) {
    throw new Error(`Application install with key '${key}' not found`);
  }

  return JSON.parse(JSON.stringify(installData)) as ApplicationInstall;
}

/**
 * Install an application for a user
 */
export async function installApplication(key: string, user: string): Promise<ApplicationInstall> {
  await delay(500);

  // In real implementation, this would POST to /api/applications/{key}/users/{user}
  const installData = mockData.applicationInstalls[key as keyof typeof mockData.applicationInstalls];
  
  if (!installData) {
    throw new Error(`Application with key '${key}' not found`);
  }

  // Return the install data (in reality, backend would create it)
  return JSON.parse(JSON.stringify(installData)) as ApplicationInstall;
}

/**
 * Uninstall an application for a user
 */
export async function uninstallApplication(key: string, user: string): Promise<void> {
  await delay(500);

  // In real implementation, this would DELETE to /api/applications/{key}/users/{user}
  console.log(`Uninstalling application ${key} for user ${user}`);
}

/**
 * Update application settings
 */
export async function updateApplicationSettings(
  key: string,
  user: string,
  settings: Record<string, unknown>
): Promise<ApplicationInstall> {
  await delay(600);

  // In real implementation, this would PUT to /api/applications/{key}/users/{user}
  const installData = mockData.applicationInstalls[key as keyof typeof mockData.applicationInstalls];
  
  if (!installData) {
    throw new Error(`Application install with key '${key}' not found`);
  }

  // Update the settings values in the mock data (for demo purposes)
  const updatedInstall = JSON.parse(JSON.stringify(installData)) as ApplicationInstall;
  updatedInstall.applicationSettings = updatedInstall.applicationSettings.map(setting => ({
    ...setting,
    value: settings[setting.key] !== undefined ? String(settings[setting.key]) : setting.value,
  }));

  return updatedInstall;
}

/**
 * Authorize an application (trigger OAuth flow)
 */
export async function authorizeApplication(key: string, user: string): Promise<string> {
  await delay(300);

  // In real implementation, this would GET to /api/applications/{key}/users/{user}/authorize
  // and return a redirect URL for OAuth
  const authUrl = `/api/applications/${key}/users/${user}/authorize/token`;
  
  console.log(`Initiating authorization for ${key}: ${authUrl}`);
  return authUrl;
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
