<script setup lang="ts">
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import Tabs, { type Tab } from '@/components/ui/Tabs.vue'
import WorkersTab from '@/components/settings/WorkersTab.vue'
import TokensTab from '@/components/settings/TokensTab.vue'
import AuditEntitiesTab from '@/components/settings/AuditEntitiesTab.vue'

const settingsTabs: Tab[] = [
  {
    id: 'workers',
    label: 'Workers',
    target: 'workers-content',
    icon: 'm234-480-12-60q-12-5-22.5-10.5T178-564l-58 18-40-68 46-40q-2-13-2-26t2-26l-46-40 40-68 58 18q11-8 21.5-13.5T222-820l12-60h80l12 60q12 5 22.5 10.5T370-796l58-18 40 68-46 40q2 13 2 26t-2 26l46 40-40 68-58-18q-11 8-21.5 13.5T326-540l-12 60h-80Zm40-120q33 0 56.5-23.5T354-680q0-33-23.5-56.5T274-760q-33 0-56.5 23.5T194-680q0 33 23.5 56.5T274-600ZM592-40l-18-84q-17-6-31.5-14.5T514-158l-80 26-56-96 64-56q-2-18-2-36t2-36l-64-56 56-96 80 26q14-11 28.5-19.5T574-516l18-84h112l18 84q17 6 31.5 14.5T782-482l80-26 56 96-64 56q2 18 2 36t-2 36l64 56-56 96-80-26q-14 11-28.5 19.5T722-124l-18 84H592Zm56-160q50 0 85-35t35-85q0-50-35-85t-85-35q-50 0-85 35t-35 85q0 50 35 85t85 35Z',
    iconViewBox: '0 -960 960 960'
  },
  {
    id: 'tokens',
    label: 'Tokens',
    target: 'tokens-content',
    icon: 'M480-80q-139-35-229.5-159.5T160-516v-244l320-120 320 120v244q0 152-90.5 276.5T480-80Zm-80-240h160q17 0 28.5-11.5T600-360v-120q0-17-11.5-28.5T560-520v-40q0-33-23.5-56.5T480-640q-33 0-56.5 23.5T400-560v40q-17 0-28.5 11.5T360-480v120q0 17 11.5 28.5T400-320Zm40-200v-40q0-17 11.5-28.5T480-600q17 0 28.5 11.5T520-560v40h-80Z',
    iconViewBox: '0 -960 960 960'
  },
  {
    id: 'audit-entities',
    label: 'Audit entities',
    target: 'audit-entities-content',
    icon: 'M160-120q-33 0-56.5-23.5T80-200v-440q0-33 23.5-56.5T160-720h160v-80q0-33 23.5-56.5T400-880h160q33 0 56.5 23.5T640-800v80h160q33 0 56.5 23.5T880-640v440q0 33-23.5 56.5T800-120H160Zm0-80h640v-440H160v440Zm240-520h160v-80H400v80ZM160-200v-440 440Z',
    iconViewBox: '0 -960 960 960'
  }
]
</script>

<template>
  <DashboardLayout>
    <!-- Page Header -->
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Settings</h1>
      <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
        Manage settings and configuration
      </p>
    </div>

    <!-- Tabs -->
    <Tabs :tabs="settingsTabs" content-id="settings-tabs-content">
      <!-- Workers Tab -->
      <div id="workers-content" role="tabpanel" aria-labelledby="workers-tab">
        <WorkersTab />
      </div>

      <!-- Tokens Tab -->
      <div
        id="tokens-content"
        role="tabpanel"
        aria-labelledby="tokens-tab"
        class="hidden"
      >
        <TokensTab />
      </div>

      <!-- Audit Entities Tab -->
      <div
        id="audit-entities-content"
        role="tabpanel"
        aria-labelledby="audit-entities-tab"
        class="hidden"
      >
        <AuditEntitiesTab />
      </div>
    </Tabs>
  </DashboardLayout>
</template>

