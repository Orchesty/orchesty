<template>
  <div v-if="loading">
    <v-row>
      <v-col>
        <base-progress-bar-linear />
      </v-col>
    </v-row>
  </div>
  <div v-else>
    <div v-if="appActive">
      <navigation-item
        :text="navigationItem.text"
        :icon="navigationItem.icon"
        :to="navigationItem.to"
        :color="navigationItem.color"
      />
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
            <uninstall-app-modal
              v-if="appActive.isInstallable"
              class="mr-3"
              color="error"
              :is-uninstalling="isUninstalling"
              :disabled="isRequestPending"
              :app-name="appActive.name"
              :on-click="() => uninstall(appActive.key)"
            />
            <v-switch
              v-if="isActivationEnabled"
              :input-value="isActivated"
              color="secondary"
              :loading="isActivationLoading"
              inset
              :disabled="activationDisabled"
              @change="onActivationChange($event)"
            >
              <template #label>
                <span class="activation-label">{{ onOrOff }}</span>
              </template>
            </v-switch>
          </div>
        </v-col>
      </v-row>

      <v-row>
        <v-col>
          <v-tabs v-model="tab" height="24">
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
                  @submit.prevent="() => saveForm(form.key, form.publicName)"
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
                      <base-input
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
                      <base-select
                        v-model="settingsForms[index].fields[field.key]"
                        :clearable="!field.readOnly"
                        :readonly="field.readOnly"
                        :disabled="field.disabled"
                        :label="field.label"
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
                      button-class="mb-3"
                    />
                    <validation-provider
                      v-if="field.type === 'checkbox'"
                      :name="field.key"
                      slim
                    >
                      <base-checkbox
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
                      <base-input
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
                  <base-button
                    type="submit"
                    color="primary"
                    :button-title="$t('button.save')"
                    :on-click="() => saveForm(form.key, form.publicName)"
                    :disabled="isRequestPending"
                    :loading="isSaving"
                  />
                  <base-button
                    v-if="hasOauthAuthorization"
                    :disabled="!isFormValid(form.key) || isRequestPending"
                    :on-click="authorizeApp"
                    :button-title="$t('button.authorize')"
                  />
                </actions-wrapper>
              </v-col>
            </v-row>
          </template>
        </v-tab-item>
      </v-tabs-items>
    </div>
  </div>
</template>

<script>
import { config } from '@/config'
import BaseInput from '@/components/commons/BaseInput'
import BaseButton from '@/components/commons/BaseButton'
import BaseCheckbox from '@/components/commons/BaseCheckbox'
import { callApi } from '@/utils/apiFetch'
import { redirectTo } from '@/utils/redirect'
import { API } from '@/api'
import NavigationItem from '@/components/commons/NavigationItem'
import { ROUTES } from '@/router/routes'
import AppItemPasswordModal from '@/components/commons/AppInstalledPasswordModal'
import ActionsWrapper from '@/components/commons/ActionsWrapper'
import BaseProgressBarLinear from '@/components/commons/BaseProgressBarLinear'
import UninstallAppModal from '@/components/applications/UninstallAppModal'
import BaseSelect from '@/components/commons/BaseSelect'
import showFlashMessage from '@/utils/flashMessage'
import { FLASH_MESSAGES_TYPES } from '@/store/flashMessages/types'

export default {
  name: 'InstalledAppDetailPage',
  components: {
    BaseSelect,
    ActionsWrapper,
    AppItemPasswordModal,
    BaseButton,
    BaseCheckbox,
    BaseInput,
    BaseProgressBarLinear,
    NavigationItem,
    UninstallAppModal,
  },
  data() {
    return {
      tab: 0,
      settingsForms: [],
      settingsConfig: [],
      settingsSnapshots: [],
      webhooksSettings: {},
      hasOauthAuthorization: false,
      appActive: null,
      loading: false,
      isUninstalling: false,
      isSaving: false,
      isActivated: false,
      isActivationEnabled: false,
      isActivationLoading: false,
      navigationItem: {
        to: ROUTES.APPLICATIONS,
        icon: 'mdi-arrow-left-circle',
        text: 'navigation.link.backToTheApplications',
        color: 'primary',
      },
      redirectTo,
    }
  },
  computed: {
    isRequestPending() {
      return this.isSaving || this.loading || this.isUninstalling
    },
    onOrOff() {
      return this.isActivated
        ? this.$t('application.activated')
        : this.$t('application.notactivated')
    },
    activationDisabled() {
      return !this.appActive.authorized
    },
  },
  methods: {
    async uninstall(key) {
      this.isUninstalling = true
      await callApi({
        requestData: API.appStore.uninstallApp,
        params: { key },
      })
      await this.redirectTo(this.$router, {
        name: ROUTES.APPLICATIONS,
      })
      this.isUninstalling = false
    },

    isFormValid(key) {
      const form = this.getFormByKey(key)
      return form.matchesWithSnapshot && form.hasValidSettings
    },

    async saveForm(key, formName) {
      const isOk = await this.$refs[key][0].validate()

      if (!isOk) {
        return
      }

      this.isSaving = true

      const form = this.getFormByKey(key)

      const formSettings = {
        [key]: form.fields,
      }

      const isSaved = await callApi({
        requestData: API.appStore.saveAppSettings,
        params: {
          key: this.appActive.key,
          data: formSettings,
        },
      })

      if (isSaved) {
        this.appActive = await callApi({
          requestData: API.appStore.getApp,
          params: { key: this.appActive.key },
        })
      }
      await this.$refs[key][0].reset()

      this.isSaving = false
      showFlashMessage(
        this.$t('flashMessage.saved', { item: formName }),
        FLASH_MESSAGES_TYPES.SUCCESS
      )
    },

    async authorizeApp() {
      this.isSaving = true
      const authorizeURL = new URL(
        `/api/applications/${this.appActive.key}/users/${this.userId}/authorize`,
        config.backend.apiBaseUrl
      )
      authorizeURL.searchParams.append('redirect_url', window.location.href)
      window.open(authorizeURL.href, '_blank').focus()
      this.isSaving = false
    },

    getEntries(choices) {
      return choices.map((choice) => {
        const [[value, key]] = Object.entries(choice)
        return {
          value,
          key,
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
          key: 'info',
          publicName: 'Info',
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
    },

    hasOauth() {
      this.hasOauthAuthorization =
        this.appActive.authorization_type.startsWith('oauth')
    },

    hasEmptySettings() {
      for (let form of this.settingsForms) {
        const hasEmptyValue = Object.values(form.fields).some((field) => {
          return field == null || field === ''
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
      return app?.logo ? app.logo : ''
    },
    async onActivationChange(newState) {
      this.isActivationLoading = true

      let result
      try {
        result = await callApi({
          requestData: API.appStore.activateApp,
          params: {
            key: this.$route.params.id,
            data: {
              enabled: newState,
            },
          },
        })
        showFlashMessage(
          this.$t(
            newState ? 'flashMessage.activated' : 'flashMessage.deactivated',
            {
              item: this.appActive.name,
            }
          ),
          FLASH_MESSAGES_TYPES.SUCCESS
        )
      } catch (err) {
        // TODO add flash message with error
        // Force rerendering of v-switch component, because it seems like can't be kept
        // in sync with this component internal state (this.isActivated)
        this.isActivationEnabled = false
        this.$nextTick(() => {
          this.isActivated = !newState
          this.isActivationEnabled = true
        })
      }
      if (result) {
        // TODO add flash message with success message
        this.isActivated = newState
      } else {
        // TODO handle wrong response
      }
      this.isActivationLoading = false
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
    this.loading = true
    this.appActive = await callApi({
      requestData: API.appStore.getApp,
      params: { key: this.$route.params.id },
    })
    this.$emit('appChanged', this.appActive.name)
    this.loading = false
  },
  beforeDestroy() {
    this.$emit('appChanged', null)
  },
}
</script>
<style scoped lang="scss">
.text-transform-none {
  text-align: start;
  text-transform: none;
  letter-spacing: 0;
}

.activation-label {
  width: 12ch;
}

.application-settings-wrapper {
  max-width: 80ch;
}

.application-settings-wrapper-form {
  max-width: 50ch;
}
</style>
