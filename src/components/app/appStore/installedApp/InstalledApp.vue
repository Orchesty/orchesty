<template>
  <div v-if="appActive">
    <v-row class="mt-4">
      <v-col cols="2">
        <v-img max-width="150" contain :src="hasLogo(appActive)" />
      </v-col>
    </v-row>
    <v-row>
      <v-col cols="5" class="d-flex justify-space-between flex-column">
        <h1 class="headline font-weight-bold">{{ appActive.name }}</h1>
        <p class="mt-4">{{ appActive.description }}</p>
        <div class="d-flex">
          <app-button
            color="error"
            class="mr-3"
            :button-title="$t('appStore.detail.uninstall')"
            :on-click="() => uninstall(appActive.key)"
          />
          <div v-for="item in appActive.applicationSettings" :key="item.key">
            <app-item-password-modal v-if="item.type === 'password'" :app-key="appActive.key" :input="item" />
          </div>
        </div>
      </v-col>
    </v-row>
    <v-row>
      <v-col cols="3">
        <validation-observer ref="applicationForm" tag="form" slim @submit.prevent="saveSettings">
          <h3 class="title font-weight-bold mb-3">{{ $t('appStore.detail.application') }}</h3>
          <v-row dense>
            <v-col>
              <div v-for="item in appActive.applicationSettings" :key="item.key">
                <validation-provider
                  v-if="item.type === 'text'"
                  v-slot="{ errors }"
                  slim
                  :name="item.key"
                  :rules="item.required ? 'required' : ''"
                >
                  <app-input
                    v-model="form.appSettings[item.key]"
                    dense
                    outlined
                    :readonly="item.readonly"
                    :disabled="item.disabled"
                    :label="item.label"
                    :error-messages="errors"
                  />
                </validation-provider>
                <validation-provider v-if="item.type === 'selectbox'" :name="item.key" slim>
                  <v-select
                    v-model="form.appSettings[item.key]"
                    dense
                    outlined
                    :readonly="item.readonly"
                    :disabled="item.disabled"
                    :label="item.label"
                    :items="getEntries(item.choices)"
                    item-value="value"
                    item-text="key"
                  />
                </validation-provider>
              </div>
            </v-col>
          </v-row>

          <v-row dense>
            <v-col class="d-flex">
              <app-button color="primary" :button-title="$t('button.save')" :on-click="saveSettings" />
              <app-button
                v-if="flags.isOauth"
                class="ml-auto"
                :disabled="!flags.hasSettings || !flags.matchSettings"
                :on-click="authorize"
                :button-title="$t('button.authorize')"
              />
            </v-col>
          </v-row>
        </validation-observer>
      </v-col>
    </v-row>
    <v-divider
      v-if="'webhookSettings' in appActive && appActive.webhookSettings.length !== 0"
      class="orchesty-divider-margin"
    />
    <v-row>
      <v-col v-if="'webhookSettings' in appActive && appActive.webhookSettings.length !== 0" cols="6">
        <h3 class="title font-weight-bold mb-3">{{ $t('appStore.detail.webhooks') }}</h3>

        <template v-for="item in appActive.webhookSettings">
          <validation-observer :key="item.name" :ref="item.name" slim @submit.prevent="saveWebhook(item.name)">
            <v-row dense>
              <v-col> <app-input dense outlined readonly label="Webhook Name" :value="item.name" /></v-col>
              <v-col>
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
                    :items="topologiesAll"
                    item-text="name"
                    item-value="name"
                    :error-messages="errors[0]"
                  />
                </validation-provider>
              </v-col>
              <v-col>
                <app-button type="submit" class="mx-auto" :button-title="getWebhookStatusButton(item.name)" />
              </v-col>
            </v-row>
          </validation-observer>
        </template>
      </v-col>
    </v-row>
  </div>
</template>

<script>
import { mapActions, mapGetters } from 'vuex'
import { APP_STORE } from '@/store/modules/appStore/types'
import { AUTH } from '@/store/modules/auth/types'
import { TOPOLOGIES } from '@/store/modules/topologies/types'
import { ROUTES } from '@/services/enums/routerEnums'
import { REQUESTS_STATE } from '@/store/modules/api/types'
import { config } from '@/config'
import AppItemPasswordModal from '@/components/app/appStore/modal/AppItemPasswordModal'
import AppInput from '@/components/commons/input/AppInput'
import AppButton from '@/components/commons/button/AppButton'

export default {
  name: 'InstalledApp',
  components: { AppButton, AppInput, AppItemPasswordModal },
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
    ...mapGetters(APP_STORE.NAMESPACE, { appActive: APP_STORE.GETTERS.GET_ACTIVE_APP }),
    ...mapGetters(TOPOLOGIES.NAMESPACE, { topologiesAll: TOPOLOGIES.GETTERS.GET_ALL_TOPOLOGIES }),
    ...mapGetters(REQUESTS_STATE.NAMESPACE, [REQUESTS_STATE.GETTERS.GET_STATE]),
    ...mapGetters(AUTH.NAMESPACE, { userId: AUTH.GETTERS.GET_LOGGED_USER_ID }),
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
      const isInstalled = await this[APP_STORE.ACTIONS.UNINSTALL_APP_REQUEST]({ key, userId: this.userId })
      if (isInstalled) {
        await this.$router.push({ name: ROUTES.APP_STORE.INSTALLED_APPS })
      }
    },
    async saveWebhook(name) {
      const isValid = await this.$refs[name][0].validate()
      if (isValid) {
        await this[APP_STORE.ACTIONS.SUBSCRIBE_WEBHOOK]({
          userId: this.userId,
          key: this.appActive.key,
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
        userId: this.userId,
        key: this.appActive.key,
        data: this.form.appSettings,
      })
      if (isSaved) {
        await this[APP_STORE.ACTIONS.GET_INSTALLED_APP]({ key: this.$route.params.key, userId: this.userId })
        this.initAuthState()
      }
    },
    async authorize() {
      let authUrl = `${config.backend.apiBaseUrl}/api/applications/${this.appActive.key}/users/${this.userId}/authorize?redirect_url=${window.location.href}`
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
        : 'empty'
    },
    getEntries(item) {
      return item.map((option) => {
        if (Object.keys(option)[0] == 0) {
          return { value: option, key: option }
        } else {
          return { value: Object.keys(option)[0], key: option[Object.keys(option)[0]] }
        }
      })
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
      this.flags.isOauth = this.appActive.authorization_type.startsWith('oauth')
      this.initialAppSettings = {}

      let initialAppSettingObjects = this.appActive.applicationSettings
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
    hasLogo(app) {
      return app?.logo ? app.logo : require('@/assets/svg/app-item-placeholder.svg')
    },
  },
  watch: {
    appActive(app) {
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
    this.initAuthState()
    this.initAppForms(this.appActive)
    this.$refs.applicationForm.reset()
  },
}
</script>
<style>
.orchesty-divider-margin {
  margin-bottom: 20px !important;
  margin-top: 25px !important;
  border-width: 1px;
  width: 250px;
  border-color: var(--v-gray-base) !important;
}
</style>
