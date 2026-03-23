<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue';
import type { WorkerGroup, ApplicationStatus } from '@/types/applications';
import { fetchApplications, installApplication } from '@/services/applicationsService';
import DashboardLayout from '@/layouts/DashboardLayout.vue';
import ApplicationCard from '@/components/applications/ApplicationCard.vue';
import ApplicationDetailDrawer from '@/components/applications/ApplicationDetailDrawer.vue';
import SearchInput from '@/components/ui/SearchInput.vue';
import { useToast } from '@/composables/useToast';

const FILTER_KEY = 'orchesty_apps_filter';
const DRAWER_KEY = 'orchesty_apps_drawer';

const savedFilter = localStorage.getItem(FILTER_KEY) as ApplicationStatus | 'all' | 'all-installed' | null;
const selectedFilter = ref<ApplicationStatus | 'all' | 'all-installed'>(savedFilter || 'all');
const workers = ref<WorkerGroup[]>([]);
const workersExpanded = ref<Record<string, boolean>>({});
const loading = ref(false);
const searchQuery = ref('');
const { showToast } = useToast();

const filteredWorkers = computed(() => {
  if (!searchQuery.value) return workers.value
  const q = searchQuery.value.toLowerCase()
  return workers.value
    .map(worker => ({
      ...worker,
      applications: worker.applications.filter(app =>
        app.name.toLowerCase().includes(q) || app.key.toLowerCase().includes(q)
      )
    }))
    .filter(worker => worker.applications.length > 0)
});

const drawerOpen = ref(false);
const selectedAppKey = ref('');
const selectedWorkerName = ref('');
const selectedAppStatus = ref<ApplicationStatus>('available');

const loadApplications = async () => {
  loading.value = true;
  try {
    let filterParam: { status?: ApplicationStatus | 'all-installed' };
    if (selectedFilter.value === 'all') {
      filterParam = { status: undefined };
    } else if (selectedFilter.value === 'all-installed') {
      filterParam = { status: 'all-installed' };
    } else {
      filterParam = { status: selectedFilter.value as ApplicationStatus };
    }

    workers.value = await fetchApplications(filterParam);

    // Initialize all workers as expanded
    workers.value.forEach(worker => {
      if (!(worker.name in workersExpanded.value)) {
        workersExpanded.value[worker.name] = true;
      }
    });
  } catch (error) {
    console.error('Failed to load applications:', error);
  } finally {
    loading.value = false;
  }
};

const handleFilterChange = (newFilter: ApplicationStatus | 'all' | 'all-installed') => {
  selectedFilter.value = newFilter;
  localStorage.setItem(FILTER_KEY, newFilter);
  loadApplications();
};

const toggleWorker = (workerName: string) => {
  workersExpanded.value[workerName] = !workersExpanded.value[workerName];
};

const saveDrawerState = () => {
  localStorage.setItem(DRAWER_KEY, JSON.stringify({
    key: selectedAppKey.value,
    worker: selectedWorkerName.value,
    status: selectedAppStatus.value,
  }));
};

const clearDrawerState = () => {
  localStorage.removeItem(DRAWER_KEY);
};

const handleOpenDetail = (appKey: string, workerName: string, status: ApplicationStatus) => {
  selectedAppKey.value = appKey;
  selectedWorkerName.value = workerName;
  selectedAppStatus.value = status;
  drawerOpen.value = true;
  saveDrawerState();
};

watch(drawerOpen, (open) => {
  if (!open) clearDrawerState();
});

const handleDrawerRefresh = () => {
  loadApplications();
};

const handleInstall = async (appKey: string, workerName: string) => {
  loading.value = true;
  try {
    const installResult = await installApplication(appKey, workerName);

    // Determine the new status based on the install result
    // The API returns authorized status in the response
    const newStatus: ApplicationStatus = installResult.authorized ? 'authorized' : 'installed';

    // Update selected app details
    selectedAppKey.value = appKey;
    selectedWorkerName.value = workerName;
    selectedAppStatus.value = newStatus;

    // Show success toast
    showToast('Application installed successfully', 'success');

    // Refresh the applications list
    await loadApplications();

    // Open the drawer with the installed app
    drawerOpen.value = true;
    saveDrawerState();
  } catch (error) {
    console.error('Failed to install application:', error);
    showToast(`Failed to install application: ${error instanceof Error ? error.message : String(error)}`, 'error');
  } finally {
    loading.value = false;
  }
};

onMounted(async () => {
  await loadApplications();

  // Restore drawer state (handles OAuth redirect, refresh, back navigation)
  const savedDrawer = localStorage.getItem(DRAWER_KEY);
  if (savedDrawer) {
    try {
      const { key, worker, status } = JSON.parse(savedDrawer);
      if (key && worker) {
        // Look up the current status from freshly loaded data
        const app = workers.value
          .flatMap(w => w.applications)
          .find(a => a.key === key);

        selectedAppKey.value = key;
        selectedWorkerName.value = worker;
        selectedAppStatus.value = app?.status ?? status ?? 'installed';
        drawerOpen.value = true;
      }
    } catch {
      localStorage.removeItem(DRAWER_KEY);
    }
  }
});
</script>

<template>
  <DashboardLayout>
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Applications</h1>
      <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Browse and manage available applications</p>
    </div>

    <!-- Radio Filter -->
    <div class="mb-6">
      <div class="mb-4 flex items-center justify-between gap-4">
        <div class="flex items-center gap-4">
        <div class="flex items-center">
          <input
            id="filter-all"
            name="app-filter"
            type="radio"
            value="all"
            :checked="selectedFilter === 'all'"
            class="h-4 w-4 border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
            @change="() => handleFilterChange('all')"
          >
          <label for="filter-all" class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300">
            All
          </label>
        </div>
        <div class="flex items-center">
          <input
            id="filter-available"
            name="app-filter"
            type="radio"
            value="available"
            :checked="selectedFilter === 'available'"
            class="h-4 w-4 border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
            @change="() => handleFilterChange('available')"
          >
          <label for="filter-available" class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300">
            Available
          </label>
        </div>
        <div class="flex items-center">
          <input
            id="filter-all-installed"
            name="app-filter"
            type="radio"
            value="all-installed"
            :checked="selectedFilter === 'all-installed'"
            class="h-4 w-4 border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
            @change="() => handleFilterChange('all-installed')"
          >
          <label for="filter-all-installed" class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300">
            Installed
          </label>
        </div>
        <div class="flex items-center">
          <input
            id="filter-unauthorized"
            name="app-filter"
            type="radio"
            value="installed"
            :checked="selectedFilter === 'installed'"
            class="h-4 w-4 border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
            @change="() => handleFilterChange('installed')"
          >
          <label for="filter-unauthorized" class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300">
            Unauthorized
          </label>
        </div>
        <div class="flex items-center">
          <input
            id="filter-authorized"
            name="app-filter"
            type="radio"
            value="authorized"
            :checked="selectedFilter === 'authorized'"
            class="h-4 w-4 border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
            @change="() => handleFilterChange('authorized')"
          >
          <label for="filter-authorized" class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300">
            Authorized
          </label>
        </div>
        <div class="flex items-center">
          <input
            id="filter-activated"
            name="app-filter"
            type="radio"
            value="activated"
            :checked="selectedFilter === 'activated'"
            class="h-4 w-4 border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
            @change="() => handleFilterChange('activated')"
          >
          <label for="filter-activated" class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300">
            Activated
          </label>
        </div>
        </div>
        <SearchInput
          v-model="searchQuery"
          placeholder="Search applications..."
          mode="client"
          width="w-64"
        />
      </div>
      <div class="border-t border-gray-200 dark:border-gray-700"></div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex items-center justify-center py-12">
      <div role="status">
        <svg
          aria-hidden="true"
          class="w-8 h-8 text-gray-200 animate-spin dark:text-gray-600 fill-primary-600"
          viewBox="0 0 100 101"
          fill="none"
          xmlns="http://www.w3.org/2000/svg"
        >
          <path
            d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z"
            fill="currentColor"
          />
          <path
            d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z"
            fill="currentFill"
          />
        </svg>
        <span class="sr-only">Loading...</span>
      </div>
    </div>

    <!-- Worker Sections -->
    <div v-else>
      <div v-for="worker in filteredWorkers" :key="worker.name" class="mb-6">
        <button
          type="button"
          class="flex items-center w-full mb-4 text-lg font-semibold text-gray-900 dark:text-white hover:text-primary-600 dark:hover:text-primary-500 transition-colors"
          @click="() => toggleWorker(worker.name)"
        >
          <svg
            class="w-5 h-5 mr-2 transition-transform duration-200"
            :class="{ 'rotate-90': workersExpanded[worker.name] }"
            aria-hidden="true"
            xmlns="http://www.w3.org/2000/svg"
            width="24"
            height="24"
            fill="none"
            viewBox="0 0 24 24"
          >
            <path
              stroke="currentColor"
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="m9 5 5 7-5 7"
            />
          </svg>
          {{ worker.name }}
        </button>

        <!-- Cards Grid -->
        <div
          v-show="workersExpanded[worker.name]"
          class="grid gap-4 sm:grid-cols-2 md:grid-cols-3"
        >
          <ApplicationCard
            v-for="app in worker.applications"
            :key="app.key"
            :application="app"
            @open-detail="handleOpenDetail"
            @install="handleInstall"
          />
        </div>
      </div>

      <!-- Empty State -->
      <div v-if="filteredWorkers.length === 0" class="text-center py-12">
        <p class="text-gray-500 dark:text-gray-400">No applications found for the selected filter.</p>
      </div>
    </div>

    <!-- Application Detail Drawer -->
    <ApplicationDetailDrawer
      v-model="drawerOpen"
      :application-key="selectedAppKey"
      :worker="selectedWorkerName"
      :status="selectedAppStatus"
      @refresh="handleDrawerRefresh"
    />
  </DashboardLayout>
</template>

