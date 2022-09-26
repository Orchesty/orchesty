<template>
  <content-basic v-if="appActive" redirect-in-title title="Back to the applications">
    <v-row v-if="appActive" class="mt-4">
      <v-col cols="2">
        <v-img max-width="150" contain :src="hasLogo(appActive)" />
      </v-col>
    </v-row>
    <v-row>
      <v-col cols="5" class="d-flex justify-space-between flex-column">
        <h1 class="headline font-weight-bold">{{ appActive.name }}</h1>
        <p class="mt-4">{{ appActive.description }}</p>
        <div v-if="appActive.isInstallable">
          <app-button color="primary" :button-title="$t('appStore.app.install')" :on-click="install" />
        </div>
      </v-col>
    </v-row>
    <v-row>
      <v-col>
        <v-tabs height="24">
          <v-tab v-if="appActive.info" class="text-transform-none body-2 font-weight-medium primary--text">
            {{ $t('appStore.app.tabs.info') }}
          </v-tab>
        </v-tabs>
      </v-col>
    </v-row>

    <v-row v-if="appActive">
      <v-col class="info-wrapper">
        <!-- eslint-disable-next-line vue/no-v-html -->
        <div v-html="appActive.info" />
      </v-col>
    </v-row>
  </content-basic>
</template>

<script>
import { mapActions, mapGetters } from 'vuex'
import { APP_STORE } from '@/store/modules/appStore/types'
import { AUTH } from '@/store/modules/auth/types'
import { ROUTES } from '@/services/enums/routerEnums'
import AppButton from '@/components/commons/button/AppButton'
import ContentBasic from '@/components/layout/content/ContentBasic'

export default {
  name: 'AvailableApp',
  components: { ContentBasic, AppButton },
  computed: {
    ...mapGetters(AUTH.NAMESPACE, { userId: AUTH.GETTERS.GET_LOGGED_USER_ID }),
    ...mapGetters(APP_STORE.NAMESPACE, { appActive: APP_STORE.GETTERS.GET_ACTIVE_APP }),
  },
  methods: {
    ...mapActions(APP_STORE.NAMESPACE, [APP_STORE.ACTIONS.INSTALL_APP_REQUEST, APP_STORE.ACTIONS.GET_AVAILABLE_APP]),
    async install() {
      let isInstalled = await this[APP_STORE.ACTIONS.INSTALL_APP_REQUEST]({
        key: this.$route.params.key,
        userId: this.userId,
      })
      if (isInstalled) {
        await this.$router.push({ name: ROUTES.APP_STORE.INSTALLED_APP, params: { key: this.$route.params.key } })
      }
    },

    hasLogo(app) {
      return app.logo ? app.logo : require('@/assets/svg/app-item-placeholder.svg')
    },
  },
  async created() {
    await this[APP_STORE.ACTIONS.GET_AVAILABLE_APP](this.$route.params.key)
  },
}
</script>

<style lang="scss" scoped>
.text-transform-none {
  text-align: start;
  text-transform: none;
  letter-spacing: 0;
}

.info-wrapper {
  max-width: 80ch;
}
</style>
