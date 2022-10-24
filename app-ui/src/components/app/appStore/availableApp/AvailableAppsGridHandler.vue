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
            <span>{{ $t("page.status.noAppsAvailable") }}</span>
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
        <template v-for="(app, index) in items">
          <app-item
            :key="index"
            :logo="app.logo"
            :title="app.name"
            :authorized="app.authorized"
            :description="app.description"
            :installed="app.installed"
          >
            <template #buttons>
              <app-item-button
                v-if="isAppInstallable(app)"
                :loading="appInProgress === app.key"
                :text="
                  app.installed ? $t('button.installed') : $t('button.install')
                "
                :color="app.installed ? 'success' : 'primary'"
                :disabled="app.installed || isRequestSending"
                class="mt-2"
                @click="install(app.key)"
              />
              <app-item-button
                outlined
                color="secondary"
                :text="$t('button.detail')"
                :to="{
                  name: app.installed
                    ? ROUTES.APP_STORE.INSTALLED_APP
                    : ROUTES.APP_STORE.DETAIL_APP,
                  params: { key: app.key },
                }"
                class="mt-2"
                :disabled="isRequestSending"
              />
            </template>
          </app-item>
        </template>
      </v-row>
    </template>
  </v-data-iterator>
</template>

<script>
import { mapActions, mapGetters } from "vuex"
import { REQUESTS_STATE } from "@/store/modules/api/types"
import { API } from "@/api"
import { APP_STORE } from "@/store/modules/appStore/types"
import { AUTH } from "@/store/modules/auth/types"
import { ROUTES } from "@/services/enums/routerEnums"
import AppItem from "@/components/app/appStore/item/AppItem"
import AppItemButton from "@/components/app/appStore/button/AppItemButton"
import ProgressBarLinear from "@/components/commons/progressIndicators/ProgressBarLinear"

export default {
  name: "AvailableAppsGridHandler",
  components: { ProgressBarLinear, AppItemButton, AppItem },
  data() {
    return {
      appInProgress: null,
      appsMerged: [],
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
      return this[REQUESTS_STATE.GETTERS.GET_STATE]([
        API.appStore.getAvailableApps.id,
        API.appStore.getInstalledApps.id,
        API.appStore.getInstalledApp.id,
        API.appStore.installApp.id,
      ]).isSending
    },
  },
  methods: {
    ...mapActions(APP_STORE.NAMESPACE, [
      APP_STORE.ACTIONS.INSTALL_APP_REQUEST,
      APP_STORE.ACTIONS.UNINSTALL_APP_REQUEST,
      APP_STORE.ACTIONS.GET_INSTALLED_APPS,
      APP_STORE.ACTIONS.GET_INSTALLED_APP,
      APP_STORE.ACTIONS.GET_AVAILABLE_APPS,
    ]),
    mergeWithInstalledApps() {
      if (this.appsAvailable)
        this.appsMerged = this.appsAvailable.map((availableAppData) => {
          const installedAppData = this.appsInstalled.find(
            (installedApp) => installedApp.key === availableAppData.key
          )
          if (installedAppData) {
            const app = {
              ...availableAppData,
              ...installedAppData,
              installed: true,
            }
            app.logo = app.logo ?? ""
            return app
          } else {
            const app = {
              ...availableAppData,
              installed: false,
              authorized: false,
            }
            app.logo = app.logo ?? ""
            return app
          }
        })
    },
    isAppInstallable(app) {
      //First condition is for the case in which the application was installed via third party resource, but is labeled as {isInstallable: false} in backend.
      return (!app.isInstallable && app.installed) || app.isInstallable
    },
    async install(key) {
      this.appInProgress = key
      await this[APP_STORE.ACTIONS.INSTALL_APP_REQUEST]({ key })
      await this[APP_STORE.ACTIONS.GET_AVAILABLE_APPS]()
      await this[APP_STORE.ACTIONS.GET_INSTALLED_APPS]()
      await this[APP_STORE.ACTIONS.GET_INSTALLED_APP]({ key })
      this.appInProgress = null
      await this.$router.push({
        name: ROUTES.APP_STORE.INSTALLED_APP,
        params: { key },
      })
    },
  },
  async created() {
    await this[APP_STORE.ACTIONS.GET_AVAILABLE_APPS]()
    await this[APP_STORE.ACTIONS.GET_INSTALLED_APPS]()
    this.mergeWithInstalledApps()
  },
}
</script>
