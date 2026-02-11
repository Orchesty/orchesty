<script setup lang="ts">
import { ref, watch, computed, nextTick } from 'vue';
import type { ApplicationInstall, ApplicationSetting, ApplicationStatus } from '@/types/applications';
import {
  fetchApplicationInstall,
  installApplication,
  uninstallApplication,
  updateApplicationSettings,
  changeApplicationState,
} from '@/services/applicationsService';
import { useToast } from '@/composables/useToast';
import Drawer from '@/components/ui/Drawer.vue';
import Button from '@/components/ui/Button.vue';
import TabsWithOverflow, { type TabDefinition } from '@/components/applications/TabsWithOverflow.vue';
import DynamicFormGenerator from '@/components/applications/DynamicFormGenerator.vue';

interface Props {
  modelValue: boolean;
  applicationKey: string;
  worker: string;
  status: ApplicationStatus;
}

const props = defineProps<Props>();

const emit = defineEmits<{
  'update:modelValue': [value: boolean];
  'refresh': [];
}>();

const { showToast } = useToast();

const loading = ref(false);
const saving = ref(false);
const applicationInstall = ref<ApplicationInstall | null>(null);
const formValues = ref<Record<string, unknown>>({});
const activeTab = ref<string>('');

// Group settings by tab
const groupedSettings = computed(() => {
  if (!applicationInstall.value) return {};

  const groups: Record<string, ApplicationSetting[]> = {};

  applicationInstall.value.applicationSettings.forEach((setting) => {
    const tab = setting.tab || 'general';
    if (!groups[tab]) {
      groups[tab] = [];
    }
    groups[tab].push(setting);
  });

  return groups;
});

// Create tab definitions
const tabs = computed<TabDefinition[]>(() => {
  return Object.keys(groupedSettings.value).map(tabKey => ({
    id: tabKey,
    label: tabKey.charAt(0).toUpperCase() + tabKey.slice(1),
  }));
});

const statusLabel = computed(() => {
  if (!applicationInstall.value) return '';
  switch (props.status) {
    case 'activated':
      return 'Activated';
    case 'authorized':
      return 'Authorized';
    case 'installed':
      return 'Unauthorized';
    case 'available':
    default:
      return '';
  }
});

const showStatusBadge = computed(() => {
  return props.status !== 'available' && applicationInstall.value;
});

const statusBadgeClass = computed(() => {
  if (!applicationInstall.value) return '';
  switch (props.status) {
    case 'activated':
      return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300';
    case 'authorized':
      return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300';
    case 'installed':
      return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300';
    case 'available':
    default:
      return '';
  }
});

const isInstallDisabled = computed(() => {
  return applicationInstall.value?.authorized ?? false;
});

const loadApplicationData = async () => {
  if (!props.applicationKey || !props.worker) return;

  loading.value = true;
  applicationInstall.value = null; // Clear previous data

  try {
    // Use preview endpoint only for 'available' apps
    const isInstalled = props.status !== 'available';

    const data = await fetchApplicationInstall(props.applicationKey, props.worker, isInstalled);

    // Initialize form values from settings
    const initialValues: Record<string, unknown> = {};
    data.applicationSettings.forEach((setting) => {
      initialValues[setting.key] = setting.value ?? '';
    });

    // Set all data at once
    applicationInstall.value = data;
    formValues.value = initialValues;

    // Wait for DOM to be ready
    await nextTick();

    // Set active tab to first tab
    const tabsList = Object.keys(groupedSettings.value);
    if (tabsList.length > 0) {
      activeTab.value = tabsList[0];
    }

    loading.value = false;
  } catch (error) {
    console.error('Failed to load application install:', error);
    showToast('Failed to load application details', 'error');
    loading.value = false;
  }
};

const handleInstall = async () => {
  if (!props.applicationKey || !props.worker) return;

  loading.value = true;
  try {
    const data = await installApplication(props.applicationKey, props.worker);
    applicationInstall.value = data;

    // Initialize form values from settings
    const initialValues: Record<string, unknown> = {};
    data.applicationSettings.forEach((setting) => {
      initialValues[setting.key] = setting.value ?? '';
    });
    formValues.value = initialValues;

    showToast('Application installed successfully', 'success');
    emit('refresh'); // Refresh parent list
  } catch (error: any) {
    console.error('Failed to install application:', error);
    showToast(error.response?.data?.message || 'Failed to install application', 'error');
  } finally {
    loading.value = false;
  }
};

const handleUninstall = async () => {
  if (!props.applicationKey || !props.worker) return;

  if (!confirm('Are you sure you want to uninstall this application?')) {
    return;
  }

  loading.value = true;
  try {
    await uninstallApplication(props.applicationKey, props.worker);
    showToast('Application uninstalled successfully', 'success');
    emit('update:modelValue', false);
    emit('refresh'); // Refresh parent list
  } catch (error: any) {
    console.error('Failed to uninstall application:', error);
    showToast(error.response?.data?.message || 'Failed to uninstall application', 'error');
  } finally {
    loading.value = false;
  }
};

const handleSave = async () => {
  if (!applicationInstall.value) return;

  saving.value = true;
  try {
    const updatedInstall = await updateApplicationSettings(
      props.applicationKey,
      props.worker,
      formValues.value,
      applicationInstall.value.applicationSettings
    );

    // Update the local state with fresh data from API
    applicationInstall.value = updatedInstall;

    // Reinitialize form values from updated settings
    const initialValues: Record<string, unknown> = {};
    updatedInstall.applicationSettings.forEach((setting) => {
      initialValues[setting.key] = setting.value ?? '';
    });
    formValues.value = initialValues;

    showToast('Settings saved successfully', 'success');
  } catch (error: any) {
    console.error('Failed to save settings:', error);
    showToast(error.response?.data?.message || 'Failed to save settings', 'error');
  } finally {
    saving.value = false;
  }
};

const handleChangeState = async (enabled: boolean) => {
  if (!props.applicationKey || !props.worker) return;

  loading.value = true;
  try {
    await changeApplicationState(props.applicationKey, props.worker, enabled);
    showToast(
      enabled ? 'Application activated successfully' : 'Application deactivated successfully',
      'success',
    );
    emit('refresh');
    emit('update:modelValue', false);
  } catch (error: unknown) {
    const errorMessage = error instanceof Error ? error.message : 'Failed to change application state';
    console.error('Failed to change application state:', error);
    showToast(errorMessage, 'error');
  } finally {
    loading.value = false;
  }
};

const handleClose = () => {
  emit('update:modelValue', false);
};

// Watch for drawer open/close
watch(() => props.modelValue, async (newValue) => {
  if (newValue && props.applicationKey) {
    await loadApplicationData();
  }
}, { immediate: true });
</script>

<template>
  <Drawer
    id="app-detail-drawer"
    :model-value="modelValue"
    :label="applicationInstall?.name || 'Application Details'"
    width="w-1/2 min-w-[500px]"
    @update:model-value="$emit('update:modelValue', $event)"
  >
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

    <div v-else-if="applicationInstall && !loading" class="space-y-6">
      <!-- Header Section -->
      <div>
        <div class="flex items-start justify-between mb-3">
          <div>
            <!-- Display API logo or fallback to generic icon -->
            <img
              v-if="applicationInstall.logo"
              :src="applicationInstall.logo"
              :alt="`${applicationInstall.name} logo`"
              class="h-12 w-12 mb-2"
            />
            <svg
              v-else
              xmlns="http://www.w3.org/2000/svg"
              class="h-12 text-gray-900 dark:text-white mb-2"
              height="48px"
              viewBox="0 -960 960 960"
              width="48px"
              fill="currentColor"
            >
              <path
                d="M240-160q-33 0-56.5-23.5T160-240q0-33 23.5-56.5T240-320q33 0 56.5 23.5T320-240q0 33-23.5 56.5T240-160Zm240 0q-33 0-56.5-23.5T400-240q0-33 23.5-56.5T480-320q33 0 56.5 23.5T560-240q0 33-23.5 56.5T480-160Zm240 0q-33 0-56.5-23.5T640-240q0-33 23.5-56.5T720-320q33 0 56.5 23.5T800-240q0 33-23.5 56.5T720-160ZM240-400q-33 0-56.5-23.5T160-480q0-33 23.5-56.5T240-560q33 0 56.5 23.5T320-480q0 33-23.5 56.5T240-400Zm240 0q-33 0-56.5-23.5T400-480q0-33 23.5-56.5T480-560q33 0 56.5 23.5T560-480q0 33-23.5 56.5T480-400Zm240 0q-33 0-56.5-23.5T640-480q0-33 23.5-56.5T720-560q33 0 56.5 23.5T800-480q0 33-23.5 56.5T720-400ZM240-640q-33 0-56.5-23.5T160-720q0-33 23.5-56.5T240-800q33 0 56.5 23.5T320-720q0 33-23.5 56.5T240-640Zm240 0q-33 0-56.5-23.5T400-720q0-33 23.5-56.5T480-800q33 0 56.5 23.5T560-720q0 33-23.5 56.5T480-640Zm240 0q-33 0-56.5-23.5T640-720q0-33 23.5-56.5T720-800q33 0 56.5 23.5T800-720q0 33-23.5 56.5T720-640Z"
              />
            </svg>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
              {{ applicationInstall.name }}
            </h2>
            <div class="flex items-center gap-2">
              <span
                v-if="showStatusBadge"
                class="text-xs font-medium px-2.5 py-0.5 rounded"
                :class="statusBadgeClass"
              >
                {{ statusLabel }}
              </span>
              <span v-if="applicationInstall.worker" class="text-sm font-medium text-gray-900 dark:text-white">
                Worker: {{ applicationInstall.worker }}
              </span>
            </div>
          </div>
          <div class="flex items-center gap-2">
            <Button
              v-if="props.status === 'available'"
              variant="primary"
              :disabled="loading"
              @click="handleInstall"
            >
              Install
            </Button>
            <Button
              v-if="props.status === 'authorized'"
              variant="primary"
              :disabled="loading"
              @click="handleChangeState(true)"
            >
              Activate
            </Button>
            <Button
              v-if="props.status === 'activated'"
              variant="outline"
              :disabled="loading"
              @click="handleChangeState(false)"
            >
              Deactivate
            </Button>
            <Button
              v-if="props.status !== 'available'"
              variant="outline"
              :disabled="loading"
              @click="handleUninstall"
            >
              Uninstall
            </Button>
          </div>
        </div>
      </div>

      <!-- Description -->
      <div>
        <p class="text-sm text-gray-500 dark:text-gray-400">
          {{ applicationInstall.description }}
        </p>
      </div>

      <!-- Tabs Section -->
      <TabsWithOverflow v-if="tabs.length > 0" :tabs="tabs" :active-tab="activeTab">
        <template v-for="tab in tabs" :key="tab.id" #[`tab-content-${tab.id}`]>
          <div class="space-y-6">
            <DynamicFormGenerator
              :settings="groupedSettings[tab.id] || []"
              v-model="formValues"
            />

            <!-- Save button for this tab -->
            <div class="flex items-center justify-start pt-4">
              <Button variant="primary" :disabled="saving" @click="handleSave">
                {{ saving ? 'Saving...' : 'Save' }}
              </Button>
            </div>
          </div>
        </template>
      </TabsWithOverflow>
    </div>

    <template #footer-actions>
      <div class="flex items-center justify-end">
        <Button variant="outline" @click="handleClose">
          Close
        </Button>
      </div>
    </template>
  </Drawer>
</template>

