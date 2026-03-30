<script setup lang="ts">
import { ref, watch, computed, nextTick } from 'vue';
import type { ApplicationInstall, ApplicationSetting, ApplicationStatus } from '@/types/applications';
import {
  fetchApplicationInstall,
  installApplication,
  uninstallApplication,
  updateApplicationSettings,
  changeApplicationState,
  authorizeApplication,
} from '@/services/applicationsService';
import { useToast } from '@/composables/useToast';
import Drawer from '@/components/ui/Drawer.vue';
import Button from '@/components/ui/Button.vue';
import Confirm from '@/components/ui/Confirm.vue';
import StatusBadge from '@/components/ui/StatusBadge.vue';
import type { BadgeVariant } from '@/components/ui/StatusBadge.vue';
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

function stripEmpty<T extends Record<string, unknown>>(obj: T): Partial<T> {
  return Object.fromEntries(
    Object.entries(obj).filter(([, v]) => v !== undefined && v !== null && v !== ''),
  ) as Partial<T>;
}

const loading = ref(false);
const saving = ref(false);
const showUninstallConfirm = ref(false);
const applicationInstall = ref<ApplicationInstall | null>(null);
const formValues = ref<Record<string, unknown>>({});
const activeTab = ref<string>('');
const formRefs = ref<Record<string, InstanceType<typeof DynamicFormGenerator>>>({});
const currentStatus = ref<ApplicationStatus>(props.status);

const setFormRef = (tabId: string, el: any) => {
  if (el) formRefs.value[tabId] = el;
};

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

// Identify the authorization tab by its formKey
const authTabId = computed<string | null>(() => {
  for (const [tabName, settings] of Object.entries(groupedSettings.value)) {
    if (settings.some(s => s.formKey?.toLowerCase().includes('authorization'))) {
      return tabName;
    }
  }
  return null;
});

const isAuthorized = computed(() => applicationInstall.value?.authorized ?? false);

const isAuthTab = (tabId: string) => tabId === authTabId.value;

// Create tab definitions — non-auth tabs are disabled until authorized
const tabs = computed<TabDefinition[]>(() => {
  const hasMultipleTabs = Object.keys(groupedSettings.value).length > 1;
  return Object.keys(groupedSettings.value).map(tabKey => ({
    id: tabKey,
    label: tabKey.charAt(0).toUpperCase() + tabKey.slice(1),
    disabled: hasMultipleTabs && !isAuthTab(tabKey) && !isAuthorized.value,
  }));
});

const statusLabel = computed(() => {
  if (!applicationInstall.value) return '';
  switch (currentStatus.value) {
    case 'activated':
      return 'Activated';
    case 'authorized':
      return 'Authorized';
    case 'installed':
      return 'Unauthorized';
    case 'available':
    default:
      return 'gray';
  }
});

const showStatusBadge = computed(() => {
  return currentStatus.value !== 'available' && applicationInstall.value;
});

const statusBadgeVariant = computed<BadgeVariant>(() => {
  switch (currentStatus.value) {
    case 'activated': return 'green';
    case 'authorized': return 'yellow';
    case 'installed': return 'red';
    case 'available':
    default:
      return 'gray';
  }
});

const isInstallDisabled = computed(() => {
  return applicationInstall.value?.authorized ?? false;
});

const isOAuthApp = computed(() =>
  ['oauth', 'oauth2'].includes(applicationInstall.value?.authorization_type ?? '')
);

const needsOAuthAuthorization = computed(() => isOAuthApp.value && !isAuthorized.value);

const getSaveButtonLabel = (tabId: string): string => {
  if (saving.value) return 'Saving...';
  if (isAuthTab(tabId) && needsOAuthAuthorization.value) return 'Save & Authorize';
  return 'Save';
};

const loadApplicationData = async () => {
  if (!props.applicationKey || !props.worker) return;

  loading.value = true;
  applicationInstall.value = null; // Clear previous data

  try {
    currentStatus.value = props.status;
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

    // Set active tab to auth tab (if exists), otherwise first tab
    const tabsList = Object.keys(groupedSettings.value);
    if (tabsList.length > 0) {
      activeTab.value = authTabId.value ?? tabsList[0] ?? '';
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

    currentStatus.value = 'installed';
    showToast('Application installed successfully', 'success');
    emit('refresh');
  } catch (error: any) {
    console.error('Failed to install application:', error);
    showToast(error.response?.data?.message || 'Failed to install application', 'error');
  } finally {
    loading.value = false;
  }
};

const handleUninstall = () => {
  showUninstallConfirm.value = true;
};

const confirmUninstall = async () => {
  if (!props.applicationKey || !props.worker) return;

  loading.value = true;
  try {
    await uninstallApplication(props.applicationKey, props.worker);
    showToast('Application uninstalled successfully', 'success');
    emit('update:modelValue', false);
    emit('refresh');
  } catch (error: any) {
    console.error('Failed to uninstall application:', error);
    showToast(error.response?.data?.message || 'Failed to uninstall application', 'error');
  } finally {
    loading.value = false;
  }
};

const handleTabSave = async (tabId: string) => {
  if (!applicationInstall.value) return;

  // Validate only the active tab's form
  const formRef = formRefs.value[tabId];
  if (formRef?.validate && !formRef.validate()) return;

  // Collect only settings that belong to this tab
  const tabSettings = groupedSettings.value[tabId] || [];
  const tabFormValues: Record<string, unknown> = {};
  tabSettings.forEach(s => { tabFormValues[s.key] = formValues.value[s.key]; });

  const shouldAuthorize = isAuthTab(tabId) && needsOAuthAuthorization.value;

  saving.value = true;
  try {
    const previousData = applicationInstall.value;
    const updatedInstall = await updateApplicationSettings(
      props.applicationKey,
      props.worker,
      tabFormValues,
      tabSettings,
    );

    applicationInstall.value = {
      ...previousData,
      ...stripEmpty(updatedInstall as unknown as Record<string, unknown>),
    } as ApplicationInstall;

    const initialValues: Record<string, unknown> = {};
    updatedInstall.applicationSettings.forEach((setting) => {
      initialValues[setting.key] = setting.value ?? '';
    });
    formValues.value = initialValues;

    if (shouldAuthorize) {
      authorizeApplication(props.applicationKey, props.worker);
      return;
    }

    if (updatedInstall.authorized && currentStatus.value === 'installed') {
      currentStatus.value = 'authorized';
      emit('refresh');
    }

    showToast('Settings saved successfully', 'success');
  } catch (error: any) {
    console.error('Failed to save settings:', error);
    showToast(error.response?.data?.message || 'Failed to save settings', 'error');
  } finally {
    saving.value = false;
  }
};

const handleReauthorize = () => {
  authorizeApplication(props.applicationKey, props.worker);
};

const handleChangeState = async (enabled: boolean) => {
  if (!props.applicationKey || !props.worker) return;

  saving.value = true;
  try {
    await changeApplicationState(props.applicationKey, props.worker, enabled);
    currentStatus.value = enabled ? 'activated' : 'authorized';
    showToast(
      enabled ? 'Application activated successfully' : 'Application deactivated successfully',
      'success',
    );
    emit('refresh');
  } catch (error: unknown) {
    const errorMessage = error instanceof Error ? error.message : 'Failed to change application state';
    console.error('Failed to change application state:', error);
    showToast(errorMessage, 'error');
  } finally {
    saving.value = false;
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
    :label="'Application Details'"
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
              <StatusBadge v-if="showStatusBadge" :variant="statusBadgeVariant">
                {{ statusLabel }}
              </StatusBadge>
              <span v-if="applicationInstall.worker" class="text-sm font-medium text-gray-900 dark:text-white">
                Worker: {{ applicationInstall.worker }}
              </span>
            </div>
          </div>
          <div class="flex items-center gap-2">
            <Button
              v-if="currentStatus === 'available'"
              variant="primary"
              :disabled="saving"
              @click="handleInstall"
            >
              Install
            </Button>
            <Button
              v-if="currentStatus === 'authorized'"
              :variant="needsOAuthAuthorization ? 'outline' : 'primary'"
              :disabled="saving || needsOAuthAuthorization"
              @click="handleChangeState(true)"
            >
              Activate
            </Button>
            <Button
              v-if="currentStatus === 'activated'"
              variant="outline"
              :disabled="saving"
              @click="handleChangeState(false)"
            >
              Deactivate
            </Button>
            <Button
              v-if="currentStatus !== 'available'"
              variant="outline"
              :disabled="saving"
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
              :ref="(el: any) => setFormRef(tab.id, el)"
              :settings="groupedSettings[tab.id] || []"
              v-model="formValues"
            />

            <!-- Save button for this tab -->
            <div class="flex items-center justify-start gap-3 pt-4">
              <Button variant="primary" :disabled="saving" @click="handleTabSave(tab.id)">
                {{ getSaveButtonLabel(tab.id) }}
              </Button>
              <Button
                v-if="isAuthTab(tab.id) && isOAuthApp && isAuthorized"
                variant="outline"
                :disabled="saving"
                @click="handleReauthorize"
              >
                Re-authorize
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

  <Confirm
    v-model="showUninstallConfirm"
    id="uninstall-app-confirm"
    confirm-text="Yes, uninstall"
    cancel-text="No, cancel"
    confirm-variant="danger"
    @confirm="confirmUninstall"
  >
    <svg class="mx-auto mb-4 h-12 w-12 text-gray-400 dark:text-gray-200" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
      <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 11V6m0 8h.01M19 10a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
    </svg>
    <h3 class="mb-2 text-lg font-normal text-gray-500 dark:text-gray-400">
      Are you sure you want to uninstall this application?
    </h3>
    <p class="text-sm text-gray-400 dark:text-gray-500">
      This action cannot be undone.
    </p>
  </Confirm>
</template>

