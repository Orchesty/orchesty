<template>
  <div>
    <v-row>
      <v-col>
        <v-tabs v-model="tab" height="40" :show-arrows="true">
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
                      :input-type="field.type"
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
                  <validation-provider
                    v-if="field.type === 'multiselect'"
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
                      :multiple="true"
                    />
                  </validation-provider>
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
                  <app-item-password-modal
                    v-if="field.type === 'password' && !form.readOnly"
                    :form-key="form.key"
                    :field-key="field.key"
                    :app-key="app.key"
                    :input="field"
                    :disabled="isRequestPending"
                    :label="field.label"
                    :name="field.name"
                    button-class="mb-3"
                  />
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
              </actions-wrapper>
            </v-col>
          </v-row>
        </template>
      </v-tab-item>
    </v-tabs-items>
  </div>
</template>

<script>
import BaseInput from "@/components/commons/BaseInput"
import BaseButton from "@/components/commons/BaseButton"
import BaseCheckbox from "@/components/commons/BaseCheckbox"
import AppItemPasswordModal from "@/components/commons/AppInstalledPasswordModal"
import ActionsWrapper from "@/components/commons/ActionsWrapper"
import BaseSelect from "@/components/commons/BaseSelect"
import { callApi } from "@/utils/apiFetch"
import { API } from "@/api"
import showFlashMessage from "@/utils/flashMessage"
import { FLASH_MESSAGES_TYPES } from "@/store/flashMessages/types"

export default {
  name: "AppForm",
  components: {
    BaseSelect,
    ActionsWrapper,
    AppItemPasswordModal,
    BaseButton,
    BaseCheckbox,
    BaseInput,
  },
  props: {
    activeApp: {
      type: Object,
      required: false,
    },
  },
  data() {
    return {
      tab: 0,
      isSaving: false,
      settingsConfig: [],
      settingsSnapshots: [],
      settingsForms: [],
      app: this.activeApp,
    }
  },
  computed: {
    isRequestPending() {
      return this.isSaving
    },
  },
  methods: {
    initSettings() {
      this.settingsConfig = Object.values(this.app.applicationSettings).filter(
        (setting) => setting.key !== "limiter_form"
      ) // limiter form is hidden

      if (this.app.info) {
        this.settingsConfig.unshift({
          info: this.app.info,
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
    isFormValid(key) {
      const form = this.getFormByKey(key)
      return form.matchesWithSnapshot && form.hasValidSettings
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
          key: this.app.key,
          data: formSettings,
        },
      })

      if (isSaved) {
        this.app = await callApi({
          requestData: API.appStore.getApp,
          params: { key: this.app.key },
        })
      }
      await this.$refs[key][0].reset()

      this.isSaving = false
      showFlashMessage(
        this.$t("flashMessage.saved", { item: formName }),
        FLASH_MESSAGES_TYPES.SUCCESS
      )
    },
  },
  watch: {
    settingsForms: {
      deep: true,
      handler() {
        this.hasMatchingSettings()
      },
    },
  },
  async created() {
    this.initSettings()
    this.hasEmptySettings()
  },
}
</script>
<style scoped lang="scss">
.text-transform-none {
  text-align: start;
  text-transform: none;
  letter-spacing: 0;
}

.application-settings-wrapper-form {
  max-width: 50ch;
}
</style>
