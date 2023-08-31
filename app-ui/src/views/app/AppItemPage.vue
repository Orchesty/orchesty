<template>
  <content-basic v-if="appActive" redirect-in-title title="Back to the applications">
    <installed-app v-if="isInstalled($route.params.key)" />
    <available-app v-else />
  </content-basic>
</template>

<script>
import ContentBasic from '@/components/layout/content/ContentBasic'
import { mapActions, mapGetters } from 'vuex'
import { REQUESTS_STATE } from '@/store/modules/api/types'
import { APP_STORE } from '@/store/modules/appStore/types'
import { API } from '@/api'
import InstalledApp from '@/components/app/appStore/installedApp/InstalledApp'
import AvailableApp from '@/components/app/appStore/availableApp/AvailableApp'
import { AUTH } from '@/store/modules/auth/types'
export default {
  name: 'AppItemPage',
  components: { AvailableApp, InstalledApp, ContentBasic },
  computed: {
    ...mapGetters(REQUESTS_STATE.NAMESPACE, [REQUESTS_STATE.GETTERS.GET_STATE]),
    ...mapGetters(AUTH.NAMESPACE, { userId: AUTH.GETTERS.GET_LOGGED_USER_ID }),
    ...mapGetters(APP_STORE.NAMESPACE, {
      appActive: APP_STORE.GETTERS.GET_ACTIVE_APP,
      isInstalled: APP_STORE.GETTERS.IS_INSTALLED,
      appsInstalled: APP_STORE.GETTERS.GET_INSTALLED_APPS,
    }),
    isLoading() {
      return this[REQUESTS_STATE.GETTERS.GET_STATE]([API.appStore.getInstalledApp, API.appStore.getAvailableApp])
    },
  },
  methods: {
    ...mapActions(APP_STORE.NAMESPACE, [
      APP_STORE.ACTIONS.GET_INSTALLED_APP,
      APP_STORE.ACTIONS.GET_AVAILABLE_APP,
      APP_STORE.ACTIONS.GET_INSTALLED_APPS,
    ]),
  },
  async created() {
    if (this.appsInstalled.length === 0) {
      await this[APP_STORE.ACTIONS.GET_INSTALLED_APPS](this.userId)
    }

    if (this.isInstalled(this.$route.params.key)) {
      await this[APP_STORE.ACTIONS.GET_INSTALLED_APP]({ key: this.$route.params.key, userId: this.userId })
    } else {
      await this[APP_STORE.ACTIONS.GET_AVAILABLE_APP](this.$route.params.key)
    }
  },
}
</script>

<style scoped></style>
