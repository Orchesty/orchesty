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
        <template v-for="item in items">
          <app-item
            v-if="!item.authorized"
            :key="item.name"
            :title="item.name"
            :description="item.description"
            :image="hasLogo(item)"
            :authorized="'authorized' in item ? item.authorized : false"
          >
            <template #redirect>
              <app-item-button :text="$t('appStore.app.installed')" class="mb-2 success" />
              <app-item-button :white="true" :text="$t('appStore.app.detail')" @click="redirectToAppDetail(item.key)" />
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
        <template v-for="item in items">
          <app-item
            v-if="item.authorized"
            :key="item.name"
            :title="item.name"
            :description="item.description"
            :image="hasLogo(item)"
            :authorized="item.authorized"
          >
            <template #redirect>
              <app-item-button v-if="'authorized' in item" :text="$t('appStore.app.installed')" class="mb-2 success" />
              <app-item-button :white="true" :text="$t('appStore.app.detail')" @click="redirectToAppDetail(item.key)" />
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
    state() {
      return this[REQUESTS_STATE.GETTERS.GET_STATE]([API.appStore.getInstalledApps.id])
    },
  },
  methods: {
    ...mapActions(APP_STORE.NAMESPACE, [
      APP_STORE.ACTIONS.UNINSTALL_APP_REQUEST,
      APP_STORE.ACTIONS.GET_AVAILABLE_APPS,
      APP_STORE.ACTIONS.GET_INSTALLED_APPS,
    ]),
    async redirectToAppDetail(key) {
      await this.$router.push({ name: ROUTES.APP_STORE.INSTALLED_APP, params: { key } })
    },
    hasLogo(item) {
      return item?.logo ? item.logo : ''
    },
    mergeWithInstalledApps() {
      this.appsMerged = this.appsInstalled.map((item) => {
        let installed = this.appsAvailable.filter((installed) => installed.key === item.key)
        if (installed.length > 0) {
          return { ...item, ...installed[0] }
        } else {
          return item
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
