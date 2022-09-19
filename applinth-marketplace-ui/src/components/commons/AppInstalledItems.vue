<template>
  <div>
    <div v-if="isLoading">
      <base-progress-bar-linear />
    </div>
    <div v-else-if="!isLoading && apps.length">
      <v-card v-for="app in apps" :key="app.key" flat outlined class="mb-2">
        <v-container fluid>
          <v-row>
            <v-col cols="auto" class="d-flex">
              <v-img
                class="ma-auto"
                max-height="70"
                max-width="70"
                contain
                :src="appLogo(app.logo)"
              />
            </v-col>
            <v-col class="d-flex">
              <sub-heading class="my-auto">{{ app.name }}</sub-heading>
            </v-col>
            <v-col class="d-flex">
              <span class="my-auto">{{
                app.authorized ? 'Authorized' : 'Unauthorized'
              }}</span>
            </v-col>
            <v-col class="d-flex flex-column justify-center align-end">
              <base-button
                :button-title="$t('button.settings')"
                :to="{
                  name: ROUTES.APPLICATION_INSTALLED,
                  params: { id: app.key },
                }"
                color="primary"
                custom-class="mb-2"
                :disabled="isUninstalling"
              />
              <base-button
                v-if="app.isInstallable"
                color="error"
                :button-title="$t('button.uninstall')"
                :on-click="() => uninstall(app.key)"
                :loading="isUninstalling"
              />
            </v-col>
          </v-row>
        </v-container>
      </v-card>
    </div>
    <div v-else>
      {{ $t('appInstalledItem.noData') }}
    </div>
  </div>
</template>

<script>
import { callApi } from '@/utils/apiFetch'
import { API } from '@/api'
import SubHeading from '@/components/commons/SubHeading'
import { ROUTES } from '@/router/routes'
import BaseButton from '@/components/commons/BaseButton'
import BaseProgressBarLinear from '@/components/commons/BaseProgressBarLinear'

export default {
  name: 'AppInstalledItems',
  components: { BaseProgressBarLinear, BaseButton, SubHeading },
  data() {
    return {
      apps: null,
      ROUTES,
      isLoading: false,
      isUninstalling: false,
    }
  },
  methods: {
    appLogo(logo) {
      return logo ?? require('@/assets/svg/app-store-item-logo-placeholder.svg')
    },
    mergeApps(installedApps, availableApps) {
      return installedApps.items.map((item) => {
        const matchingApp = availableApps.items.find((app) => {
          return item.key.toLowerCase() === app.key.toLowerCase()
        })
        return {
          ...item,
          logo: matchingApp?.logo,
          name: matchingApp?.name,
        }
      })
    },

    async uninstall(key) {
      this.isUninstalling = true
      await callApi({
        requestData: API.appStore.uninstallApp,
        params: { key },
      })
      this.isUninstalling = false
      await this.fetchApplications()
    },
    async fetchApplications() {
      this.isLoading = true

      try {
        const availableApps = await callApi({
          requestData: API.appStore.getAvailableApps,
        })
        const installedApps = await callApi({
          requestData: API.appStore.getInstalledApps,
        })
        this.apps = this.mergeApps(installedApps, availableApps)
      } catch {
        this.apps = []
      } finally {
        this.isLoading = false
      }
    },
  },
  async created() {
    await this.fetchApplications()
  },
}
</script>

<style scoped></style>
