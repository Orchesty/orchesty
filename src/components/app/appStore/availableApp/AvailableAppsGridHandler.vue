<template>
  <v-data-iterator
    :items="appsMerged"
    :loading="state.isSending"
    hide-default-footer
    :items-per-page="Number.MAX_SAFE_INTEGER"
  >
    <template #no-data>
      <v-container fluid>
        <v-row>
          <v-col class="px-0">
            <span>No apps available</span>
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
        <template v-for="item in items">
          <app-item
            :key="item.name"
            :image="hasLogo(item)"
            :title="item.name"
            :description="item.description"
            :installed="item.installed"
            :authorized="'authorized' in item ? item.authorized : false"
          >
            <template #redirect>
              <app-item-button v-if="'authorized' in item" :text="$t('appStore.app.installed')" class="success" />
              <app-item-button v-else :text="$t('appStore.app.install')" @click="appInstall(item.key)" />
              <app-item-button
                :white="true"
                :text="$t('appStore.app.detail')"
                @click="redirectToAppPage(item.key, item.installed)"
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
import { APP_STORE } from '@/store/modules/appStore/types'
import { AUTH } from '@/store/modules/auth/types'
import { ROUTES } from '@/services/enums/routerEnums'
import AppItem from '@/components/app/appStore/item/AppItem'
import AppItemButton from '@/components/app/appStore/button/AppItemButton'
import ProgressBarLinear from '@/components/commons/progressIndicators/ProgressBarLinear'

export default {
  name: 'AvailableAppsGridHandler',
  components: { ProgressBarLinear, AppItemButton, AppItem },
  data() {
    return {
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
    state() {
      return this[REQUESTS_STATE.GETTERS.GET_STATE]([
        API.appStore.getAvailableApps.id,
        API.appStore.getInstalledApps.id,
        API.appStore.getInstalledApp.id,
        API.appStore.installApp.id,
      ])
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
    hasLogo(item) {
      return item?.logo ? item.logo : ''
    },
    mergeWithInstalledApps() {
      if (this.appsAvailable)
        this.appsMerged = this.appsAvailable.map((item) => {
          let installed = this.appsInstalled.filter((installed) => installed.key === item.key)
          if (installed.length > 0) {
            return { ...item, ...installed[0], installed: true }
          } else {
            return { ...item, installed: false }
          }
        })
    },
    async redirectToAppPage(key, isInstalled) {
      if (isInstalled) {
        await this.$router.push({ name: ROUTES.APP_STORE.INSTALLED_APP, params: { key } })
      } else {
        await this.$router.push({ name: ROUTES.APP_STORE.DETAIL_APP, params: { key } })
      }
    },
    async appInstall(key) {
      await this[APP_STORE.ACTIONS.INSTALL_APP_REQUEST]({ key, userId: this.userId })
      await this[APP_STORE.ACTIONS.GET_AVAILABLE_APPS]()
      await this[APP_STORE.ACTIONS.GET_INSTALLED_APPS](this.userId)
      await this[APP_STORE.ACTIONS.GET_INSTALLED_APP]({ key, userId: this.userId })
      await this.$router.push({ name: ROUTES.APP_STORE.INSTALLED_APP, params: { key } })
    },
  },
  async created() {
    await this[APP_STORE.ACTIONS.GET_AVAILABLE_APPS]()
    await this[APP_STORE.ACTIONS.GET_INSTALLED_APPS](this.userId)
    this.mergeWithInstalledApps()
  },
}
</script>
