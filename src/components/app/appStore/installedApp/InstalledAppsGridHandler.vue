<template>
  <data-grid
    is-iterator
    :headers="headers"
    :is-loading="state.isSending"
    :namespace="DATA_GRIDS.INSTALLED_APPS"
    disable-headers
    disabled-advanced-filter
    disable-filter
    disable-pagination
    :request-params="{ id: user.user.id }"
    :extended-iterator="true"
  >
    <template #default>
      <v-row class="mt-3">
        <v-col class="d-flex">
          <h5>{{ $t('appStore.unauthorized') }}</h5>
        </v-col>
      </v-row>
      <v-row>
        <template v-for="item in data">
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
              <app-item-button :white="true" :text="$t('appStore.app.detail')" @click="detail(item.key)" />
            </template>
          </app-item>
        </template>
      </v-row>
    </template>
    <template #extended>
      <v-row class="mt-5">
        <v-col class="d-flex">
          <h5>{{ $t('appStore.authorized') }}</h5>
        </v-col>
      </v-row>
      <v-row>
        <template v-for="item in data">
          <app-item
            v-if="item.authorized"
            :key="item.name"
            :title="item.name"
            :description="item.description"
            :image="hasLogo(item)"
          >
            <template #redirect>
              <app-item-button v-if="'authorized' in item" :text="$t('appStore.app.installed')" class="mb-2 success" />
              <app-item-button :white="true" :text="$t('appStore.app.detail')" @click="detail(item.key)" />
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
import { REQUESTS_STATE } from '../../../../store/modules/api/types'
import { API } from '../../../../api'
import AppItem from '../itemApp/AppItem'
import { AUTH } from '../../../../store/modules/auth/types'
import { ROUTES } from '../../../../services/enums/routerEnums'
import AppItemButton from '@/components/app/appStore/buttonApp/AppItemButton'
import { APP_STORE } from '@/store/modules/appStore/types'

export default {
  name: 'InstalledAppsGridHandler',
  components: { AppItemButton, AppItem, DataGrid },
  computed: {
    ...mapState(AUTH.NAMESPACE, ['user']),
    ...mapState(DATA_GRIDS.INSTALLED_APPS, ['items']),
    ...mapState(APP_STORE.NAMESPACE, ['available']),
    ...mapGetters(REQUESTS_STATE.NAMESPACE, [REQUESTS_STATE.GETTERS.GET_STATE]),
    state() {
      return this[REQUESTS_STATE.GETTERS.GET_STATE]([API.appStore.getInstalledApps.id])
    },
  },
  methods: {
    ...mapActions(APP_STORE.NAMESPACE, [APP_STORE.ACTIONS.UNINSTALL_APP_REQUEST, APP_STORE.ACTIONS.GET_AVAILABLE_APPS]),
    detail(key) {
      this.$router.push({ name: ROUTES.APP_STORE.INSTALLED_APP, params: { key } })
    },
    async uninstall(key) {
      await this[APP_STORE.ACTIONS.UNINSTALL_APP_REQUEST]({ key, userId: this.user.user.id })
    },
    mergeWithInstalledApps() {
      this.data = this.items.map((item) => {
        let installed = this.available.filter((installed) => installed.key === item.key)
        if (installed.length > 0) {
          return { ...item, ...installed[0] }
        } else {
          return item
        }
      })
    },
    hasLogo(item) {
      return item?.logo ? item.logo : ''
    },
  },
  watch: {
    async items() {
      await this[APP_STORE.ACTIONS.GET_AVAILABLE_APPS]()
      this.mergeWithInstalledApps()
    },
  },
  async mounted() {
    await this[APP_STORE.ACTIONS.GET_AVAILABLE_APPS]()
    this.mergeWithInstalledApps()
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
