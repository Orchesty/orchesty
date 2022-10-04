<template>
  <div>
    <v-row>
      <v-col>
        <heading>{{ $t('applicationsPage.heading') }}</heading>
      </v-col>
    </v-row>
    <v-row v-if="isLoading">
      <v-col>
        <base-progress-bar-linear />
      </v-col>
    </v-row>
    <v-row v-else-if="!isLoading && apps.length">
      <app-store-item
        v-for="(app, index) in apps"
        :key="index"
        :logo="app.logo"
        :title="app.name"
        :authorized="app.authorized"
        :description="app.description"
        :installed="app.installed"
      >
        <template #buttons>
          <app-store-item-button
            v-if="isAppInstallable(app)"
            :loading="appInProgress === app.key"
            :text="
              app.installed ? $t('button.installed') : $t('button.install')
            "
            :color="app.installed ? 'success' : 'primary'"
            :disabled="app.installed || isRequestSending"
            class="mt-2"
            @click="install(app.key, app.name)"
          />
          <app-store-item-button
            outlined
            color="secondary"
            :text="$t('button.detail')"
            :to="{
              name: app.installed
                ? ROUTES.APPLICATION_INSTALLED
                : ROUTES.APPLICATION_AVAILABLE,
              params: { id: app.key },
            }"
            class="mt-2"
            :disabled="isRequestSending"
          />
        </template>
      </app-store-item>
    </v-row>
    <v-row v-else>
      <v-col>
        {{ $t('applicationsPage.noData') }}
      </v-col>
    </v-row>
  </div>
</template>

<script>
import AppStoreItem from '@/components/commons/AppStoreItem'
import { callApi } from '@/utils/apiFetch'
import { API } from '@/api'
import AppStoreItemButton from '@/components/commons/AppStoreItemButton'
import { ROUTES } from '@/router/routes'
import Heading from '@/components/commons/Heading'
import BaseProgressBarLinear from '@/components/commons/BaseProgressBarLinear'
import showFlashMessage from '@/utils/flashMessage'
import { FLASH_MESSAGES_TYPES } from '@/store/flashMessages/types'

export default {
  name: 'ApplicationsPage',
  components: {
    BaseProgressBarLinear,
    Heading,
    AppStoreItemButton,
    AppStoreItem,
  },
  data() {
    return {
      apps: null,
      ROUTES,
      isLoading: false,
      appInProgress: null,
    }
  },
  methods: {
    async install(key, name) {
      this.appInProgress = key
      this.isLoading = true

      await callApi({
        requestData: API.appStore.installApp,
        params: { key },
      })

      this.appInProgress = null
      this.isLoading = false

      await this.$router.push({
        name: ROUTES.APPLICATION_INSTALLED,
        params: { id: key },
      })
      showFlashMessage(
        this.$t('flashMessage.installed', { item: name }),
        FLASH_MESSAGES_TYPES.SUCCESS
      )
    },
    async initData() {
      this.isLoading = true
      const availableAppsResponse = await callApi({
        requestData: API.appStore.getAvailableApps,
      })
      const installedAppsResponse = await callApi({
        requestData: API.appStore.getInstalledApps,
      })
      if (availableAppsResponse.items && installedAppsResponse.items) {
        this.apps = availableAppsResponse.items.map((availableAppData) => {
          const installedAppData = installedAppsResponse.items.find(
            (installedApp) => installedApp.key === availableAppData.key
          )
          if (installedAppData) {
            const app = {
              ...availableAppData,
              ...installedAppData,
              installed: true,
            }
            app.logo = app.logo ?? ''
            return app
          } else {
            const app = {
              ...availableAppData,
              installed: false,
              authorized: false,
            }
            app.logo = app.logo ?? ''
            return app
          }
        })
      }
      this.isLoading = false
    },
    isAppInstallable(app) {
      //First condition is for the case in which the application was installed via third party resource, but is labeled as {isInstallable: false} in backend.
      return (!app.isInstallable && app.installed) || app.isInstallable
    },
  },
  async created() {
    await this.initData()
  },
}
</script>
