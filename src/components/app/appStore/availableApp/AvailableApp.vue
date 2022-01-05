<template>
  <div v-if="app">
    <v-row>
      <v-col>
        <v-card elevation="0">
          <v-container fluid>
            <v-row>
              <v-col cols="6">
                <v-row>
                  <v-col cols="12" class="d-flex">
                    <v-btn icon>
                      <v-icon size="30" @click="goBack">mdi-arrow-left</v-icon>
                    </v-btn>
                    <div class="ml-3">
                      <h2>{{ app.name }}</h2>
                    </div>
                  </v-col>
                </v-row>
                <v-row>
                  <v-col class="mb-3">
                    <v-img max-width="150" contain :src="hasLogo(app)" />
                  </v-col>
                </v-row>
                <v-row>
                  <v-col>
                    <v-btn class="ml-3" color="primary" max-width="150">
                      <span @click="install">{{ $t('appStore.app.install') }}</span>
                    </v-btn>
                  </v-col>
                </v-row>
                <v-row>
                  <v-col class="mt-2">
                    <p>{{ app.description }}</p>
                  </v-col>
                </v-row>
              </v-col>
            </v-row>
          </v-container>
        </v-card>
      </v-col>
    </v-row>
  </div>
</template>

<script>
import { mapActions, mapState } from 'vuex'
import { APP_STORE } from '@/store/modules/appStore/types'
import { AUTH } from '@/store/modules/auth/types'
import { ROUTES } from '@/services/enums/routerEnums'

export default {
  name: 'AppStoreItemDetail',
  methods: {
    ...mapActions(APP_STORE.NAMESPACE, [APP_STORE.ACTIONS.INSTALL_APP_REQUEST, APP_STORE.ACTIONS.GET_AVAILABLE_APP]),
    async install() {
      let isInstalled = await this[APP_STORE.ACTIONS.INSTALL_APP_REQUEST]({
        key: this.$route.params.key,
        userId: this.user.user.id,
      })
      if (isInstalled) {
        await this.$router.push({ name: ROUTES.APP_STORE.INSTALLED_APP, params: { key: this.$route.params.key } })
      }
    },

    async goBack() {
      await this.$router.push({ name: ROUTES.APP_STORE.AVAILABLE_APPS })
    },

    hasLogo(app) {
      return app.logo ? app.logo : require('@/assets/svg/app_placeholder.svg')
    },
  },
  computed: {
    ...mapState(APP_STORE.NAMESPACE, ['app']),
    ...mapState(AUTH.NAMESPACE, ['user']),
  },
  async created() {
    await this[APP_STORE.ACTIONS.GET_AVAILABLE_APP]({ key: this.$route.params.key })
  },
}
</script>
