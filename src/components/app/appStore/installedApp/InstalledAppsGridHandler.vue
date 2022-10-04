<template>
  <v-data-iterator
    :items="appsMerged"
    :loading="isRequestSending"
    hide-default-footer
    :items-per-page="Number.MAX_SAFE_INTEGER"
  >
    <template #no-data>
      <v-container fluid>
        <v-row>
          <v-col class="px-0">
            <span>No apps are installed</span>
          </v-col>
        </v-row>
      </v-container>
    </template>
    <template #loading>
      <v-container fluid>
        <v-row>
          <v-col class="px-0">
            <progress-bar-linear />
          </v-col>
        </v-row>
      </v-container>
    </template>
    <template #default="{ items }">
      <v-row>
        <v-col class="d-flex">
          <h5>{{ $t('appStore.unauthorized') }}</h5>
        </v-col>
      </v-row>
      <v-row>
        <template v-for="(app, index) in items">
          <app-item
            v-if="!app.authorized"
            :key="index"
            :logo="app.logo"
            :title="app.name"
            :authorized="app.authorized"
            :description="app.description"
            installed
          >
            <template #buttons>
              <app-item-button color="primary" disabled :text="$t('appStore.app.installed')" />
              <app-item-button
                outlined
                color="secondary"
                :text="$t('button.detail')"
                :to="{
                  name: ROUTES.APP_STORE.INSTALLED_APP,
                  params: { key: app.key },
                }"
                class="mt-2"
              />
            </template>
          </app-item>
        </template>
      </v-row>
      <v-row class="mt-5">
        <v-col class="d-flex">
          <h5>{{ $t('appStore.authorized') }}</h5>
        </v-col>
      </v-row>
      <v-row>
        <template v-for="(app, index) in items">
          <app-item
            v-if="app.authorized"
            :key="index"
            :logo="app.logo"
            :title="app.name"
            :authorized="app.authorized"
            :description="app.description"
            installed
          >
            <template #buttons>
              <app-item-button disabled color="primary" :text="$t('appStore.app.installed')" />
              <app-item-button
                outlined
                color="secondary"
                :text="$t('button.detail')"
                :to="{
                  name: ROUTES.APP_STORE.INSTALLED_APP,
                  params: { key: app.key },
                }"
                class="mt-2"
              />
            </template>
          </app-item>
        </template>
      </v-row>
    </template>
  </v-data-iterator>
</template>

<script>
import { mapActions, mapGetters } from 'vuex'
import { REQUESTS_STATE } from '@/store/modules/api/types'
import { API } from '@/api'
import AppItem from '../item/AppItem'
import { AUTH } from '@/store/modules/auth/types'
import { ROUTES } from '@/services/enums/routerEnums'
import AppItemButton from '@/components/app/appStore/button/AppItemButton'
import { APP_STORE } from '@/store/modules/appStore/types'
import ProgressBarLinear from '@/components/commons/progressIndicators/ProgressBarLinear'

export default {
  name: 'InstalledAppsGridHandler',
  components: { ProgressBarLinear, AppItemButton, AppItem },
  data() {
    return {
      appsMerged: [],
      headers: [],
      ROUTES,
    }
  },
  computed: {
    ...mapGetters(REQUESTS_STATE.NAMESPACE, [REQUESTS_STATE.GETTERS.GET_STATE]),
    ...mapGetters(AUTH.NAMESPACE, { userId: AUTH.GETTERS.GET_LOGGED_USER_ID }),
    ...mapGetters(APP_STORE.NAMESPACE, {
      appsAvailable: APP_STORE.GETTERS.GET_AVAILABLE_APPS,
      appsInstalled: APP_STORE.GETTERS.GET_INSTALLED_APPS,
    }),
    isRequestSending() {
      return this[REQUESTS_STATE.GETTERS.GET_STATE]([API.appStore.getInstalledApps.id]).isSending
    },
  },
  methods: {
    ...mapActions(APP_STORE.NAMESPACE, [
      APP_STORE.ACTIONS.UNINSTALL_APP_REQUEST,
      APP_STORE.ACTIONS.GET_AVAILABLE_APPS,
      APP_STORE.ACTIONS.GET_INSTALLED_APPS,
    ]),
    mergeWithInstalledApps() {
      this.appsMerged = this.appsInstalled.map((availableAppData) => {
        const installedAppData = this.appsAvailable.find((installedApp) => installedApp.key === availableAppData.key)
        if (installedAppData) {
          const app = { ...availableAppData, ...installedAppData }
          app.logo = app.logo ?? ''
          return app
        }
      })
    },
    async uninstall(key) {
      await this[APP_STORE.ACTIONS.UNINSTALL_APP_REQUEST]({ key, userId: this.userId })
    },
  },
  async created() {
    await this[APP_STORE.ACTIONS.GET_AVAILABLE_APPS]()
    await this[APP_STORE.ACTIONS.GET_INSTALLED_APPS](this.userId)
    this.mergeWithInstalledApps()
  },
}
</script>

<style lang="scss" scoped></style>
