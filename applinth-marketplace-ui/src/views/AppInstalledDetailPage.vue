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
        <v-col cols="5" class="d-flex justify-space-between flex-column">
          <h1 class="headline font-weight-bold">{{ appActive.name }}</h1>
          <p class="mt-4">{{ appActive.description }}</p>
          <div class="d-flex">
            <base-button
              color="error"
              class="mr-3"
              :button-title="$t('button.uninstall')"
              :on-click="() => uninstall(appActive.key)"
              :disabled="isRequestPending"
              :loading="isUninstalling"
            />
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
          class="w-400"
        >
          <v-row v-if="form.description.length > 0" dense class="mt-2">
            {{ form.description }}
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
                  <validation-provider
                    v-if="field.type === 'text'"
                    v-slot="{ errors }"
                    slim
                    :name="field.key"
                    :rules="field.required ? 'required' : ''"
                  >
                    <base-input
                      v-model="settingsForms[index].fields[field.key]"
                      dense
                      outlined
                      :readonly="field.readonly"
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
                    <v-select
                      v-model="settingsForms[index].fields[field.key]"
                      dense
                      outlined
                      :readonly="field.readonly"
                      :disabled="field.disabled"
                      :label="field.label"
                      :items="getEntries(field.choices)"
                      item-value="value"
                      item-text="key"
                    />
                  </validation-provider>
                  <app-item-password-modal
                    v-if="field.type === 'password' && !form.readOnly"
                    :form-key="form.key"
                    :field-key="field.key"
                    :app-key="appActive.key"
                    :input="field"
                    :disabled="isRequestPending"
                  />
                </div>
              </validation-observer>
            </v-col>
          </v-row>

          <v-row v-if="!form.readOnly" dense>
            <v-col>
              <actions-wrapper>
                <base-button
                  color="primary"
                  :button-title="$t('button.save')"
                  :on-click="() => saveForm(form.key)"
                  :disabled="isRequestPending"
                  :loading="isSaving"
                />
                <base-button
                  v-if="hasOauthAuthorization"
                  :disabled="!isFormValid(form.key) || isRequestPending"
                  :loading="isSaving"
                  :on-click="authorizeApp"
                  :button-title="$t('button.authorize')"
                />
              </actions-wrapper>
            </v-col>
          </v-row>
        </v-tab-item>
      </v-tabs-items>
    </div>
  </div>
</template>

<script>
import { config } from '@/config'
import BaseInput from '@/components/commons/BaseInput'
import BaseButton from '@/components/commons/BaseButton'
import { callApi } from '@/utils/apiFetch'
import { redirectTo } from '@/utils/redirect'
import { API } from '@/api'
import NavigationItem from '@/components/commons/NavigationItem'
import { ROUTES } from '@/router/routes'
import AppItemPasswordModal from '@/components/commons/AppInstalledPasswordModal'
import ActionsWrapper from '@/components/commons/ActionsWrapper'
import BaseProgressBarLinear from '@/components/commons/BaseProgressBarLinear'

export default {
  name: 'InstalledApp',
  components: {
    BaseProgressBarLinear,
    AppItemPasswordModal,
    ActionsWrapper,
    NavigationItem,
    BaseButton,
    BaseInput,
  },
  data() {
    return {
      tab: null,
      settingsForms: [],
      settingsConfig: [],
      settingsSnapshots: [],
      webhooksSettings: {},
      hasOauthAuthorization: false,
      appActive: null,
      loading: false,
      isUninstalling: false,
      isSaving: false,
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

    async saveForm(key) {
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
      this.isSaving = false
    },

    async authorizeApp() {
      this.isSaving = true
      const authorizeURL = new URL(
        `/api/application/${this.appActive.key}/authorize`,
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
      this.settingsConfig = Object.values(this.appActive.applicationSettings)

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

.w-400 {
  max-width: 400px;
}
</style>
