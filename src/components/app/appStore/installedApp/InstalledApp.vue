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
                    <v-img max-width="150" contain :src="getLogo(app)" />
                  </v-col>
                </v-row>
                <v-row>
                  <v-col>
                    <v-btn max-width="150" @click="uninstall(app.key)">
                      <span>{{ $t('appStore.detail.uninstall') }}</span>
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
    <v-row>
      <v-col cols="3">
        <v-card v-if="app" elevation="0">
          <v-container>
            <validation-observer ref="applicationForm" slim @submit.prevent="saveSettings">
              <v-row>
                <v-col>
                  <h3>{{ $t('appStore.detail.application') }}</h3>
                </v-col>
              </v-row>
              <v-row v-for="item in app.applicationSettings" :key="item.key" dense>
                <v-col>
                  <validation-provider
                    v-if="item.type === 'text'"
                    v-slot="{ errors }"
                    slim
                    :name="item.key"
                    :rules="item.required ? 'required' : ''"
                  >
                    <v-text-field
                      v-model="form.appSettings[item.key]"
                      dense
                      outlined
                      :readonly="item.readonly"
                      :disabled="item.disabled"
                      :label="item.label"
                      :error-messages="errors[0]"
                    />
                  </validation-provider>
                  <!--                  <validation-provider v-if="item.type === 'password'">-->
                  <!--                    <v-btn>Set password</v-btn>-->
                  <!--                  </validation-provider>-->
                  <validation-provider v-if="item.type === 'selectbox'" :name="item.key" slim>
                    <v-select
                      v-model="form.appSettings[item.key]"
                      dense
                      :readonly="item.readonly"
                      :disabled="item.disabled"
                      :label="item.label"
                      :items="getEntries(item.choices)"
                      item-value="0"
                      item-text="1"
                    />
                  </validation-provider>
                </v-col>
              </v-row>
              <v-row dense>
                <v-col>
                  <v-btn color="primary" @click="saveSettings">
                    <span>{{ $t('button.save') }}</span>
                  </v-btn>
                  <v-btn
                    v-if="flags.isOauth"
                    color="white"
                    :disabled="!flags.hasSettings || !flags.matchSettings"
                    class="ml-2"
                    @click="authorize"
                  >
                    <span>{{ $t('button.authorize') }}</span>
                  </v-btn>
                </v-col>
              </v-row>
            </validation-observer>
          </v-container>
        </v-card>
      </v-col>
    </v-row>
    <v-row>
      <v-col cols="6">
        <v-card elevation="0">
          <v-container>
            <v-row>
              <v-col>
                <h3 class="title font-weight-bold mb-4">{{ $t('appStore.detail.webhooks') }}</h3>
              </v-col>
            </v-row>
            <template v-if="'webhookSettings' in app && app.webhookSettings.length !== 0">
              <template v-for="item in app.webhookSettings">
                <validation-observer :key="item.name" :ref="item.name" slim @submit.prevent="saveWebhook(item.name)">
                  <v-row>
                    <v-col cols="12" lg="5">
                      <v-text-field dense outlined readonly label="Webhook Name" :value="item.name" />
                    </v-col>
                    <v-col cols="12" lg="5">
                      <validation-provider v-slot="{ errors }" :name="item.name" rules="required">
                        <v-autocomplete
                          v-if="form.webhooks"
                          v-model="form.webhooks[item.name].topology"
                          dense
                          :readonly="item.default"
                          :disabled="item.enabled"
                          label="Topology"
                          outlined
                          clearable
                          :items="topologies"
                          item-text="name"
                          item-value="name"
                          :error-messages="errors[0]"
                        />
                      </validation-provider>
                    </v-col>
                    <v-col cols="12" lg="2">
                      <v-btn height="40" type="submit" color="primary" class="mx-auto">
                        {{ getWebhookStatusButton(item.name) }}
                      </v-btn>
                    </v-col>
                  </v-row>
                </validation-observer>
              </template>
            </template>
          </v-container>
        </v-card>
      </v-col>
    </v-row>
  </div>
</template>

<script>
import { mapActions, mapGetters, mapState } from 'vuex'
import { APP_STORE } from '@/store/modules/appStore/types'
import { AUTH } from '@/store/modules/auth/types'
import { TOPOLOGIES } from '@/store/modules/topologies/types'
import { ROUTES } from '@/services/enums/routerEnums'
import { REQUESTS_STATE } from '@/store/modules/api/types'
import { config } from '@/config'

export default {
  name: 'InstalledApp',
  data() {
    return {
      form: {
        appSettings: {},
        webhookSettings: {},
      },
      flags: {
        matchSettings: true,
        hasSettings: false,
        isOauth: false,
      },
      initialAppSettings: null,
    }
  },
  computed: {
    ...mapState(TOPOLOGIES.NAMESPACE, ['topologies']),
    ...mapState(APP_STORE.NAMESPACE, ['app']),
    ...mapState(AUTH.NAMESPACE, ['user']),
    ...mapGetters(REQUESTS_STATE.NAMESPACE, [REQUESTS_STATE.GETTERS.GET_STATE]),
  },
  methods: {
    ...mapActions(TOPOLOGIES.NAMESPACE, [TOPOLOGIES.ACTIONS.DATA.GET_TOPOLOGIES]),
    ...mapActions(APP_STORE.NAMESPACE, [
      APP_STORE.ACTIONS.GET_INSTALLED_APP,
      APP_STORE.ACTIONS.SAVE_APP_SETTINGS,
      APP_STORE.ACTIONS.SUBSCRIBE_WEBHOOK,
      APP_STORE.ACTIONS.UNINSTALL_APP_REQUEST,
      APP_STORE.ACTIONS.AUTHORIZE,
    ]),
    async uninstall(key) {
      const isInstalled = await this[APP_STORE.ACTIONS.UNINSTALL_APP_REQUEST]({ key, userId: this.user.user.id })
      if (isInstalled) {
        await this.$router.push({ name: ROUTES.APP_STORE.INSTALLED_APPS })
      }
    },
    async saveWebhook(name) {
      const isValid = await this.$refs[name][0].validate()
      if (isValid) {
        await this[APP_STORE.ACTIONS.SUBSCRIBE_WEBHOOK]({
          userId: this.user.user.id,
          key: this.app.key,
          data: { name, topology: this.form.webhooks[name].topology },
        })
      }
    },
    async saveSettings() {
      const isValid = await this.$refs.applicationForm.validate()
      if (!isValid) {
        return
      }
      const isSaved = await this[APP_STORE.ACTIONS.SAVE_APP_SETTINGS]({
        userId: this.user.user.id,
        key: this.app.key,
        data: this.form.appSettings,
      })
      if (isSaved) {
        await this[APP_STORE.ACTIONS.GET_INSTALLED_APP]({ key: this.$route.params.key, userId: this.user.user.id })
        this.initAuthState()
      }
    },
    async authorize() {
      let authUrl = `${config.backend.apiBaseUrl}/api/applications/${this.app.key}/users/${this.user.user.id}/authorize?redirect_url=${window.location.href}`
      if (!config.backend.apiBaseUrl.startsWith('http')) {
        authUrl = 'https://'.concat(authUrl)
      }
      window.open(authUrl, '_blank').focus()
    },
    async goBack() {
      await this.$router.push({ name: ROUTES.APP_STORE.INSTALLED_APPS })
    },

    getWebhookStatusButton(name) {
      return this.form.webhooks
        ? this.form.webhooks[name].enabled
          ? this.$t('button.unsubscribe')
          : this.$t('button.subscribe')
        : false
    },
    getEntries(item) {
      return Object.entries(item)
    },
    initAppForms(settings) {
      if (!settings) {
        return
      }
      let webhooks = Object.entries(settings.webhookSettings)
      let data = Object.entries(settings.applicationSettings)
      let formData = { appSettings: {}, webhooks: {} }
      data.forEach((item) => {
        formData.appSettings[item[1].key] = item[1].value
      })
      webhooks.forEach((item) => {
        formData.webhooks[item[1].name] = item[1]
      })
      this.form = { ...formData }
    },
    initAuthState() {
      this.flags.matchSettings = true
      this.flags.hasSettings = false
      this.flags.isOauth = this.app.authorization_type.startsWith('oauth')
      this.initialAppSettings = {}

      let initialAppSettingObjects = this.app.applicationSettings
        .filter((setting) => setting.key !== 'pass')
        .map((setting) => ({ [setting.key]: setting.value }))

      initialAppSettingObjects.forEach((object) => {
        Object.assign(this.initialAppSettings, object)
      })
      initialAppSettingObjects.forEach((object) => {
        if (Object.values(object).filter((value) => Boolean(value)).length) {
          this.flags.hasSettings = true
        }
      })
    },
    compareSettings(formData) {
      if (!this.initialAppSettings) {
        return
      }
      let keys = Object.keys(this.initialAppSettings)
      this.flags.matchSettings = !keys.some((key) => {
        return this.initialAppSettings[key] !== formData[key]
      })
    },
    getLogo(app) {
      return app?.logo ? app.logo : require('@/assets/svg/app_placeholder.svg')
    },
  },
  watch: {
    app(app) {
      this.initAppForms(app)
    },
    'form.appSettings': {
      deep: true,
      handler(formData) {
        this.compareSettings(formData)
      },
    },
  },
  async created() {
    await this[TOPOLOGIES.ACTIONS.DATA.GET_TOPOLOGIES]()
    await this[APP_STORE.ACTIONS.GET_INSTALLED_APP]({ key: this.$route.params.key, userId: this.user.user.id })
    this.initAuthState()
  },
}
</script>
