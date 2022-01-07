<template>
  <data-grid
    is-iterator
    :headers="headers"
    :is-loading="state.isSending"
    :namespace="DATA_GRIDS.AVAILABLE_APPS"
    disable-headers
    disabled-advanced-filter
    disable-filter
    disable-pagination
  >
    <template #default>
      <!--      <v-row class="px-2">-->
      <!--        <v-col cols="12" md="auto" class="d-flex">-->
      <!--          <h5>{{ $t('appStore.authorized') }}</h5>-->
      <!--          <v-icon small class="ml-3" color="success"> mdi-circle </v-icon>-->
      <!--        </v-col>-->
      <!--        <v-col cols="12" md="auto" class="d-flex">-->
      <!--          <h5>{{ $t('appStore.unauthorized') }}</h5>-->
      <!--          <v-icon small class="ml-3" color="error"> mdi-circle </v-icon>-->
      <!--        </v-col>-->
      <!--      </v-row>-->
      <v-row class="px-2 mt-4">
        <template v-for="item in data">
          <app-item
            :key="item.name"
            :image="hasLogo(item)"
            :title="item.name"
            :description="item.description"
            :installed="item.installed"
            :authorized="'authorized' in item ? item.authorized : false"
          >
            <template #redirect>
              <app-item-button v-if="'authorized' in item" :text="$t('appStore.app.installed')" class="mb-2 success" />
              <app-item-button v-else :text="$t('appStore.app.install')" class="mb-2" @click="install(item.key)" />
              <app-item-button
                :white="true"
                :text="$t('appStore.app.detail')"
                @click="appDetailRedirect(item.key, item.installed)"
              />
            </template>
          </app-item>
        </template>
      </v-row>
    </template>
  </data-grid>
</template>

<script>
import DataGrid from '../../../commons/table/DataGrid'
import { DATA_GRIDS } from '@/services/enums/dataGridEnums'
import { mapActions, mapGetters, mapState } from 'vuex'
import { REQUESTS_STATE } from '@/store/modules/api/types'
import { API } from '@/api'
import AppItem from '../item/AppItem'
import { APP_STORE } from '@/store/modules/appStore/types'
import { AUTH } from '@/store/modules/auth/types'
import { ROUTES } from '@/services/enums/routerEnums'
import AppItemButton from '@/components/app/appStore/button/AppItemButton'

export default {
  name: 'AvailableAppsGridHandler',
  components: { AppItemButton, AppItem, DataGrid },
  computed: {
    ...mapState(AUTH.NAMESPACE, ['user']),
    ...mapState(DATA_GRIDS.AVAILABLE_APPS, ['items']),
    ...mapState(APP_STORE.NAMESPACE, ['installed']),
    ...mapGetters(REQUESTS_STATE.NAMESPACE, [REQUESTS_STATE.GETTERS.GET_STATE]),
    state() {
      return this[REQUESTS_STATE.GETTERS.GET_STATE]([API.appStore.getAvailableApps.id])
    },
  },
  methods: {
    ...mapActions(APP_STORE.NAMESPACE, [
      APP_STORE.ACTIONS.INSTALL_APP_REQUEST,
      APP_STORE.ACTIONS.UNINSTALL_APP_REQUEST,
      APP_STORE.ACTIONS.GET_INSTALLED_APPS,
    ]),
    async appDetailRedirect(key, isInstalled) {
      if (isInstalled) {
        await this.$router.push({ name: ROUTES.APP_STORE.INSTALLED_APP, params: { key } })
      } else {
        await this.$router.push({ name: ROUTES.APP_STORE.DETAIL_APP, params: { key } })
      }
    },
    async install(key) {
      await this[APP_STORE.ACTIONS.INSTALL_APP_REQUEST]({ key, userId: this.user.user.id })
      await this.$router.push({ name: ROUTES.APP_STORE.INSTALLED_APP, params: { key } })
    },
    async uninstall(key) {
      await this[APP_STORE.ACTIONS.UNINSTALL_APP_REQUEST]({ key, userId: this.user.user.id })
      await this[APP_STORE.ACTIONS.GET_INSTALLED_APPS]({ userId: this.user.user.id })
      this.mergeWithInstalledApps()
    },
    mergeWithInstalledApps() {
      if (this.items)
        this.data = this.items.map((item) => {
          let installed = this.installed.filter((installed) => installed.key === item.key)
          if (installed.length > 0) {
            return { ...item, ...installed[0], installed: true }
          } else {
            return { ...item, installed: false }
          }
        })
    },
    hasLogo(item) {
      return item?.logo ? item.logo : ''
    },
  },
  async created() {
    await this[APP_STORE.ACTIONS.GET_INSTALLED_APPS]({ userId: this.user.user.id })
    this.mergeWithInstalledApps()
  },
  watch: {
    items: {
      immediate: true,
      handler() {
        this.mergeWithInstalledApps()
      },
    },
  },
  data() {
    return {
      data: [],
      DATA_GRIDS,
      ROUTES,
      headers: [],
    }
  },
}
</script>
