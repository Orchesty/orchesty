<template>
  <content-basic
    v-if="appActive"
    redirect-in-title
    :title="$t('navigation.backToTheApplications')"
  >
    <v-row class="mt-4">
      <v-col cols="2">
        <v-img max-width="150" contain :src="hasLogo(appActive)" />
      </v-col>
    </v-row>
    <v-row>
      <v-col
        class="d-flex justify-space-between flex-column application-settings-wrapper"
      >
        <h1 class="headline font-weight-bold">{{ appActive.name }}</h1>
        <p class="mt-4">{{ appActive.description }}</p>
        <div class="d-flex justify-space-between align-center">
          <div>
            <uninstall-app-modal
              :app-name="appActive.name"
              :is-uninstalling="isUninstalling"
              :on-click="() => uninstall(appActive.key)"
            />

            <app-button
              v-if="hasOauthAuthorization"
              class="ml-2"
              :disabled="isRequestPending"
              :on-click="authorizeApp"
              :button-title="$t('button.authorize')"
            />
          </div>

          <template v-if="isActivationEnabled">
            <div class="ml-auto">
              <div v-if="activationDisabled" @click="toggleModal">
                <v-switch v-model="isActivated" color="secondary" disabled>
                  <template #label>
                    <span class="activation-label">{{ onOrOff }}</span>
                  </template>
                </v-switch>
              </div>

              <v-switch
                v-else
                v-model="isActivated"
                color="secondary"
                :loading="isActivationLoading"
                @change="onActivationChange($event)"
              >
                <template #label>
                  <span class="activation-label">{{ onOrOff }}</span>
                </template>
              </v-switch>
            </div>

            <CustomActionsMenu
              :disabled="customActionsDisabled"
              :custom-actions="customActions"
            />
          </template>

          <app-not-authorized-modal v-model="showModal" />
        </div>
      </v-col>
    </v-row>

    <v-row>
      <v-col>
        <v-tabs v-model="tab" height="40">
          <v-tab
            v-for="form in settingsConfig"
            :key="form.key"
            class="text-transform-none body-2 font-weight-medium primary--text"
          >
            {{ form.publicName }}
          </v-tab>
        </v-tabs>
      </v-col>
    </v-row>

    <v-tabs-items v-model="tab" class="mt-4">
      <v-tab-item
        v-for="(form, index) in settingsConfig"
        :key="form.key"
        class="application-settings-wrapper-form"
      >
        <template v-if="form.key === 'info'">
          <!-- eslint-disable-next-line vue/no-v-html -->
          <div class="mt-2" v-html="form.info" />
        </template>
        <template v-else>
          <v-row v-if="form.description.length > 0" dense class="mt-2">
            <v-col>
              {{ form.description }}
            </v-col>
          </v-row>
          <v-row dense class="mt-2">
            <v-col>
              <validation-observer
                :ref="form.key"
                tag="form"
                slim
                @submit.prevent="() => saveForm(form.key)"
              >
                <div v-for="field in form.fields" :key="field.key">
                  <div v-if="field.description" class="mb-2">
                    {{ field.description }}
                  </div>
                  <validation-provider
                    v-if="field.type === 'text' || field.type === 'url'"
                    v-slot="{ errors }"
                    slim
                    :name="field.key"
                    :rules="{
                      required: field.required,
                      url: field.type === 'url',
                    }"
                  >
                    <app-input
                      v-model="settingsForms[index].fields[field.key]"
                      :readonly="field.readOnly"
                      :disabled="field.disabled"
                      :label="field.label"
                      :error-messages="errors"
                    />
                  </validation-provider>
                  <validation-provider
                    v-if="field.type === 'selectbox'"
                    :name="field.key"
                    slim
                  >
                    <app-select
                      v-model="settingsForms[index].fields[field.key]"
                      :clearable="!field.readOnly"
                      :readonly="field.readOnly"
                      :disabled="field.disabled"
                      :label="field.label"
                      :items="getEntries(field.choices)"
                    />
                  </validation-provider>
                  <validation-provider
                    v-if="field.type === 'multiselect'"
                    :name="field.key"
                    slim
                  >
                    <app-select
                      v-model="settingsForms[index].fields[field.key]"
                      :clearable="!field.readOnly"
                      :readonly="field.readOnly"
                      :disabled="field.disabled"
                      :label="field.label"
                      :multiple="true"
                      :items="getEntries(field.choices)"
                    />
                  </validation-provider>
                  <app-item-password-modal
                    v-if="field.type === 'password' && !form.readOnly"
                    :form-key="form.key"
                    :field-key="field.key"
                    :app-key="appActive.key"
                    :input="field"
                    :disabled="isRequestPending"
                    :label="field.label"
                    :name="field.name"
                    button-class="mb-3"
                  />
                  <validation-provider
                    v-if="field.type === 'checkbox'"
                    :name="field.key"
                    slim
                  >
                    <app-checkbox
                      v-model="settingsForms[index].fields[field.key]"
                      :readonly="field.readOnly"
                      :disabled="field.disabled"
                      :label="field.label"
                      class="ml-3"
                    />
                  </validation-provider>
                  <validation-provider
                    v-if="field.type === 'number'"
                    v-slot="{ errors }"
                    slim
                    :name="field.key"
                    :rules="{
                      required: field.required,
                      numeric: true,
                    }"
                  >
                    <app-input
                      v-model="settingsForms[index].fields[field.key]"
                      :readonly="field.readOnly"
                      :disabled="field.disabled"
                      :label="field.label"
                      :error-messages="errors"
                    />
                  </validation-provider>
                </div>
              </validation-observer>
            </v-col>
          </v-row>

          <v-row v-if="!form.readOnly" dense>
            <v-col>
              <actions-wrapper>
                <app-button
                  type="submit"
                  color="primary"
                  :button-title="$t('button.save')"
                  :on-click="() => saveForm(form.key)"
                  :disabled="isRequestPending"
                  :loading="isSaving"
                />
              </actions-wrapper>
            </v-col>
          </v-row>
        </template>
      </v-tab-item>
    </v-tabs-items>

    <v-divider v-if="hasWebhookSettings" class="orchesty-divider-margin" />
    <v-row>
      <v-col v-if="hasWebhookSettings" cols="6">
        <h3 class="title font-weight-bold mb-3">
          {{ $t("page.heading.webhooks") }}
        </h3>

        <template v-for="item in webhooksSettings">
          <validation-observer
            :key="item.name"
            :ref="item.name"
            slim
            @submit.prevent="saveWebhook(item.name)"
          >
            <v-row dense>
              <v-col>
                <app-input readonly label="Webhook Name" :value="item.name"
              /></v-col>
              <v-col>
                <validation-provider
                  v-slot="{ errors }"
                  :name="item.name"
                  rules="required"
                >
                  <v-autocomplete
                    v-model="webhooksSettings[item.name].topology"
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
                <app-button
                  type="submit"
                  class="mx-auto"
                  :on-click="() => saveWebhook(item.name)"
                  :button-title="getWebhookStatusButton(item.name)"
                />
              </v-col>
            </v-row>
          </validation-observer>
        </template>
      </v-col>
    </v-row>
  </content-basic>
</template>

<script>
import { mapActions, mapGetters } from "vuex"
import { APP_STORE } from "@/store/modules/appStore/types"
import { AUTH } from "@/store/modules/auth/types"
import { TOPOLOGIES } from "@/store/modules/topologies/types"
import { ROUTES } from "@/services/enums/routerEnums"
import { REQUESTS_STATE } from "@/store/modules/api/types"
import { config } from "@/config"
import AppItemPasswordModal from "@/components/app/appStore/modal/AppItemPasswordModal"
import AppInput from "@/components/commons/input/AppInput"
import AppButton from "@/components/commons/button/AppButton"
import ActionsWrapper from "@/components/layout/actions/ActionsWrapper"
import ContentBasic from "@/components/layout/content/ContentBasic"
import UninstallAppModal from "@/components/app/appStore/modal/UninstallAppModal"
import { API } from "@/api"
import AppSelect from "@/components/commons/AppSelect"
import AppCheckbox from "@/components/commons/AppCheckbox"
import AppNotAuthorizedModal from "../modal/AppNotAuthorizedModal"
import { LOCAL_STORAGE } from "@/services/enums/localStorageEnums"
import CustomActionsMenu from "@/components/app/appStore/installedApp/CustomActionsMenu"

export default {
  name: "InstalledApp",
  components: {
    AppNotAuthorizedModal,
    AppCheckbox,
    AppSelect,
    UninstallAppModal,
    ContentBasic,
    ActionsWrapper,
    AppButton,
    AppInput,
    AppItemPasswordModal,
    CustomActionsMenu,
  },
  data() {
    return {
      showModal: false,
      tab: 0,
      settingsForms: [],
      settingsConfig: [],
      settingsSnapshots: [],
      webhooksSettings: {},
      hasOauthAuthorization: false,
      isActivated: false,
      isActivationEnabled: false,
      isActivationLoading: false,
    }
  },
  computed: {
    ...mapGetters(APP_STORE.NAMESPACE, {
      appActive: APP_STORE.GETTERS.GET_ACTIVE_APP,
    }),
    ...mapGetters(TOPOLOGIES.NAMESPACE, {
      topologiesAll: TOPOLOGIES.GETTERS.GET_ALL_TOPOLOGIES,
    }),
    ...mapGetters(REQUESTS_STATE.NAMESPACE, [REQUESTS_STATE.GETTERS.GET_STATE]),
    ...mapGetters(AUTH.NAMESPACE, { userId: AUTH.GETTERS.GET_LOGGED_USER_ID }),
    hasWebhookSettings() {
      return Object.entries(this.webhooksSettings).length > 0
    },
    isUninstalling() {
      const state = this[REQUESTS_STATE.GETTERS.GET_STATE]([
        API.appStore.uninstallApp.id,
      ])
      return state?.isSending || false
    },
    isSaving() {
      const state = this[REQUESTS_STATE.GETTERS.GET_STATE]([
        API.appStore.saveSettings.id,
      ])
      return state?.isSending || false
    },
    isRequestPending() {
      const state = this[REQUESTS_STATE.GETTERS.GET_STATE]([
        API.appStore.saveSettings.id,
        API.appStore.uninstallApp.id,
        API.appStore.activateApp.id,
      ])
      return state?.isSending || false
    },
    activationDisabled() {
      return !this.appActive.authorized
    },
    onOrOff() {
      return this.isActivated
        ? this.$t("page.status.activated")
        : this.$t("page.status.notActivated")
    },
    customActionsDisabled() {
      return !(this.customActions.length > 0 && !this.activationDisabled)
    },
    customActions() {
      return this.appActive.customActions || []
    },
  },
  methods: {
    ...mapActions(TOPOLOGIES.NAMESPACE, [
      TOPOLOGIES.ACTIONS.DATA.GET_TOPOLOGIES,
    ]),
    ...mapActions(APP_STORE.NAMESPACE, [
      APP_STORE.ACTIONS.GET_INSTALLED_APP,
      APP_STORE.ACTIONS.SAVE_APP_SETTINGS,
      APP_STORE.ACTIONS.SUBSCRIBE_WEBHOOK,
      APP_STORE.ACTIONS.UNSUBSCRIBE_WEBHOOK,
      APP_STORE.ACTIONS.UNINSTALL_APP_REQUEST,
      APP_STORE.ACTIONS.AUTHORIZE,
      APP_STORE.ACTIONS.ACTIVATE,
      APP_STORE.ACTIONS.RESET,
    ]),

    async uninstall(key) {
      const isInstalled = await this[APP_STORE.ACTIONS.UNINSTALL_APP_REQUEST]({
        key,
      })
      if (isInstalled) {
        await this.$router.push({ name: ROUTES.APP_STORE.INSTALLED_APPS })
      }
    },

    async saveWebhook(name) {
      const isValid = await this.$refs[name][0].validate()
      if (isValid) {
        if (this.webhooksSettings[name].enabled) {
          const result = await this[APP_STORE.ACTIONS.UNSUBSCRIBE_WEBHOOK]({
            key: this.appActive.key,
            data: { name, topology: this.webhooksSettings[name].topology },
          })
          if (result) {
            this.webhooksSettings[name].enabled = false
            // Forcing rerender of this component to trigger the generation of
            // button titles again. Would be better to implement with computed
            // properties or watchers, but refactoring is for another time.
            this.$forceUpdate()
          }
        } else {
          const result = await this[APP_STORE.ACTIONS.SUBSCRIBE_WEBHOOK]({
            key: this.appActive.key,
            data: { name, topology: this.webhooksSettings[name].topology },
          })
          if (result) {
            this.webhooksSettings[name].enabled = true
            // Forcing rerender of this component to trigger the generation of
            // button titles again. Would be better to implement with computed
            // properties or watchers, but refactoring is for another time.
            this.$forceUpdate()
          }
        }
      }
    },

    async onActivationChange(newState) {
      this.isActivationLoading = true

      const isActivated = await this[APP_STORE.ACTIONS.ACTIVATE]({
        key: this.$route.params.key,
        data: {
          enabled: newState,
        },
      })
      if (isActivated) {
        this.isActivated = newState
      } else {
        // Force rerendering of v-switch component, because it seems like can't be kept
        // in sync with this component internal state (this.isActivated)
        this.isActivationEnabled = false
        this.$nextTick(() => {
          this.isActivated = !newState
          this.isActivationEnabled = true
        })
      }

      this.isActivationLoading = false
    },

    toggleModal() {
      this.showModal = !this.showModal
    },
    isFormValid(key) {
      const form = this.getFormByKey(key)
      return form.matchesWithSnapshot && form.hasValidSettings
    },

    async saveForm(key) {
      const isValid = await this.$refs[key][0].validate()

      if (!isValid) {
        return
      }

      const form = this.getFormByKey(key)

      const formSettings = {
        [key]: form.fields,
      }

      const isSaved = await this[APP_STORE.ACTIONS.SAVE_APP_SETTINGS]({
        key: this.appActive.key,
        data: formSettings,
      })

      if (isSaved) {
        await this[APP_STORE.ACTIONS.GET_INSTALLED_APP]({
          key: this.$route.params.key,
        })
      }
    },
    async authorizeApp() {
      const authorizeURL = new URL(
        `/api/applications/${this.appActive.key}/authorize`,
        config.backend.apiBaseUrl
      )
      authorizeURL.searchParams.append("redirect_url", window.location.href)
      authorizeURL.searchParams.append(
        "Authorization",
        localStorage.getItem(LOCAL_STORAGE.USER_TOKEN)
      )
      window.open(authorizeURL.href, "_blank").focus()

      let authUrl = authorizeURL.href
      if (!config.backend.apiBaseUrl.startsWith("http")) {
        authUrl = "https://".concat(authUrl)
      }
      window.open(authUrl, "_blank").focus()
    },

    getWebhookStatusButton(name) {
      return this.webhooksSettings
        ? this.webhooksSettings[name].enabled
          ? this.$t("button.unsubscribe")
          : this.$t("button.subscribe")
        : "empty"
    },
    getEntries(item) {
      return item.map((option) => {
        if (Object.keys(option)[0] == 0) {
          return { value: option, key: option }
        } else {
          return {
            value: Object.keys(option)[0],
            key: option[Object.keys(option)[0]],
          }
        }
      })
    },
    initSettings() {
      this.isActivated = this.appActive.enabled
      this.isActivationEnabled = Boolean(this.appActive.applicationSettings)
      this.settingsConfig = Object.values(this.appActive.applicationSettings)

      if (this.appActive.info) {
        this.settingsConfig.unshift({
          info: this.appActive.info,
          key: "info",
          publicName: "Info",
          fields: [],
        })
      }

      this.settingsSnapshots = this.settingsConfig.map((form) => ({
        key: form.key,
        fields: Object.fromEntries(
          form.fields.map((field) => [field.key, field.value])
        ),
      }))

      this.settingsForms = this.settingsConfig.map((form) => ({
        key: form.key,
        fields: Object.fromEntries(
          form.fields.map((field) => [field.key, field.value])
        ),
        matchesWithSnapshot: true,
        hasValidSettings: true,
      }))

      this.webhooksSettings = {}

      this.appActive.webhookSettings.forEach((webhook) => {
        this.webhooksSettings[webhook.name] = {
          topology: webhook.topology,
          name: webhook.name,
          enabled: webhook.enabled,
        }
      })
    },

    hasOauth() {
      this.hasOauthAuthorization =
        this.appActive.authorization_type.startsWith("oauth")
    },

    hasEmptySettings() {
      for (let form of this.settingsForms) {
        const hasEmptyValue = Object.values(form.fields).some((field) => {
          return field == null || field === ""
        })
        if (hasEmptyValue) {
          form.hasValidSettings = false
        }
      }
    },

    areFormsMatching(keys, modifiedForm, snapshot) {
      return keys.every(
        (key) => snapshot.fields[key] === modifiedForm.fields[key]
      )
    },

    getFormByKey(key) {
      return this.settingsForms.find((form) => form.key === key)
    },

    hasMatchingSettings() {
      for (const snapshot of this.settingsSnapshots) {
        let modifiedForm = this.getFormByKey(snapshot.key)
        const keys = Object.keys(snapshot.fields)

        modifiedForm.matchesWithSnapshot = this.areFormsMatching(
          keys,
          modifiedForm,
          snapshot
        )
      }
    },
    hasLogo(app) {
      return app?.logo
        ? app.logo
        : require("@/assets/svg/app-item-placeholder.svg")
    },
  },

  watch: {
    appActive: {
      immediate: true,
      handler() {
        if (this.appActive) {
          this.initSettings()
          this.hasEmptySettings()
          this.hasOauth()
        }
      },
    },
    settingsForms: {
      deep: true,
      handler() {
        if (this.appActive) {
          this.hasMatchingSettings()
        }
      },
    },
  },
  async created() {
    await this[TOPOLOGIES.ACTIONS.DATA.GET_TOPOLOGIES]()
    await this[APP_STORE.ACTIONS.GET_INSTALLED_APP]({
      key: this.$route.params.key,
    })
  },
  beforeDestroy() {
    this[APP_STORE.ACTIONS.RESET]()
  },
}
</script>
<style scoped lang="scss">
.text-transform-none {
  text-align: start;
  text-transform: none;
  letter-spacing: 0;
}

.orchesty-divider-margin {
  margin-bottom: 20px !important;
  margin-top: 25px !important;
  border-width: 1px;
  width: 250px;
  border-color: var(--v-gray-base) !important;
}

.application-settings-wrapper {
  max-width: 80ch;
}

.application-settings-wrapper-form {
  max-width: 50ch;
}

.activation-label {
  width: 12ch;
}
</style>
