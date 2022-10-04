<template>
  <div>
    <div v-if="isLoading">
      <base-progress-bar-linear />
    </div>
    <div v-if="apps.length">
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
                app.authorized
                  ? $t('appInstalledItem.authorized')
                  : $t('appInstalledItem.unauthorized')
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
                :disabled="isUninstalling || isLoading"
              />
              <uninstall-app-modal
                v-if="app.isInstallable"
                color="error"
                :disabled="isLoading"
                :is-uninstalling="isUninstalling"
                :app-name="app.name"
                :on-click="() => uninstall(app.key)"
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
import UninstallAppModal from '@/components/applications/UninstallAppModal'

export default {
  name: 'AppInstalledItems',
  components: {
    BaseProgressBarLinear,
    BaseButton,
    SubHeading,
    UninstallAppModal,
  },
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
          isInstallable: matchingApp?.isInstallable,
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
