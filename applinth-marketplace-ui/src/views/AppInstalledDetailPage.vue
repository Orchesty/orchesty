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
            <div>
              <uninstall-app-modal
                v-if="appActive.isInstallable"
                class="mr-3"
                color="error"
                :is-uninstalling="isUninstalling"
                :disabled="isRequestPending"
                :app-name="appActive.name"
                :on-click="() => uninstall(appActive.key)"
              />
              <base-button
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
                  :loading="false"
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
      <AppForm :active-app="appActive" @appFormSaved="onFormSaved" />
    </div>
  </div>
</template>

<script>
import { config } from "@/config"
import BaseButton from "@/components/commons/BaseButton"
import { callApi } from "@/utils/apiFetch"
import { redirectTo } from "@/utils/redirect"
import { API } from "@/api"
import NavigationItem from "@/components/commons/NavigationItem"
import { ROUTES } from "@/router/routes"
import BaseProgressBarLinear from "@/components/commons/BaseProgressBarLinear"
import UninstallAppModal from "@/components/applications/UninstallAppModal"
import showFlashMessage from "@/utils/flashMessage"
import { FLASH_MESSAGES_TYPES } from "@/store/flashMessages/types"
import AppNotAuthorizedModal from "@/components/applications/AppNotAuthorizedModal"
import { authService } from "@/utils/authService"
import CustomActionsMenu from "@/components/applications/CustomActionsMenu.vue"
import AppForm from "@/components/applications/AppForm.vue"
import { APP_STORE } from "@/store/appStore/types"
import { mapActions, mapGetters } from "vuex"

export default {
  name: "InstalledAppDetailPage",
  components: {
    AppForm,
    CustomActionsMenu,
    AppNotAuthorizedModal,
    BaseButton,
    BaseProgressBarLinear,
    NavigationItem,
    UninstallAppModal,
  },
  data() {
    return {
      showModal: false,
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
        icon: "mdi-arrow-left-circle",
        text: "navigation.link.backToTheApplications",
        color: "primary",
      },
      redirectTo,
    }
  },
  computed: {
    ...mapGetters(APP_STORE.NAMESPACE, {
      sdk: APP_STORE.GETTERS.GET_SDK,
    }),
    isRequestPending() {
      return this.isSaving || this.loading || this.isUninstalling
    },
    activationDisabled() {
      return !this.appActive.authorized
    },
    onOrOff() {
      return this.isActivated
        ? this.$t("application.activated")
        : this.$t("application.notactivated")
    },
    customActionsDisabled() {
      return !(this.customActions.length > 0 && !this.activationDisabled)
    },
    customActions() {
      return this.appActive.customActions || []
    },
  },
  methods: {
    ...mapActions(APP_STORE.NAMESPACE, [APP_STORE.ACTIONS.GET_SDK]),
    toggleModal() {
      this.showModal = !this.showModal
    },
    async onActivationChange(newState) {
      this.isActivationLoading = true

      let result
      try {
        result = await callApi({
          requestData: API.appStore.activateApp,
          params: {
            key: this.$route.params.id,
            sdk: this.sdk,
            data: {
              enabled: newState,
            },
          },
        })
      } catch (err) {
        showFlashMessage(err.message, FLASH_MESSAGES_TYPES.ERROR)

        // Force rerendering of v-switch component, because it seems like can't be kept
        // in sync with this component internal state (this.isActivated)
        this.isActivationEnabled = false
        this.$nextTick(() => {
          this.isActivated = !newState
          this.isActivationEnabled = true
          this.isActivationLoading = false
        })
      }

      if (result) {
        showFlashMessage(
          this.$t(
            newState ? "flashMessage.activated" : "flashMessage.deactivated",
            {
              item: this.appActive.name,
            }
          ),
          FLASH_MESSAGES_TYPES.SUCCESS
        )
        this.isActivated = newState
      } else {
        // TODO handle wrong response
      }
      this.isActivationLoading = false
    },

    async uninstall(key) {
      this.isUninstalling = true
      await callApi({
        requestData: API.appStore.uninstallApp,
        params: {
          key,
          sdk: this.sdk,
        },
      })
      await this.redirectTo(this.$router, {
        name: ROUTES.APPLICATIONS,
      })
      this.isUninstalling = false
    },

    async authorizeApp() {
      this.isSaving = true
      const authorizeURL = new URL(
        API.authorize.getAuthorizationApplicationLink(
          this.appActive.key,
          this.sdk
        ),
        config.backend.apiBaseUrl
      )
      authorizeURL.searchParams.append(
        "redirect_url",
        `${window.location.href}?sdk=${this.sdk}`
      )
      authorizeURL.searchParams.append("Authorization", authService.accessToken)
      window.open(authorizeURL.href, "_blank").focus()
      this.isSaving = false
    },

    hasOauth() {
      this.hasOauthAuthorization =
        this.appActive.authorization_type.startsWith("oauth")
    },

    hasLogo(app) {
      return app?.logo ? app.logo : ""
    },

    async onFormSaved() {
      this.appActive = await callApi({
        requestData: API.appStore.getApp,
        params: {
          key: this.$route.params.id,
          sdk: this.sdk,
        },
      })
      this.isActivationEnabled = Boolean(this.appActive.applicationSettings)
    },
  },
  watch: {
    appActive: {
      immediate: true,
      handler() {
        if (this.appActive) {
          this.hasOauth()
          this.isActivationEnabled = Boolean(this.appActive.applicationSettings)
          this.isActivated = this.appActive.enabled
        }
      },
    },
  },
  async created() {
    if (this.sdk) {
      this.loading = true
      this.appActive = await callApi({
        requestData: API.appStore.getApp,
        params: {
          key: this.$route.params.id,
          sdk: this.sdk,
        },
      })
      this.$emit("appChanged", this.appActive.name)
      this.loading = false

      if (this.$route.query.sdk) {
        await this.$router.push({
          name: ROUTES.APPLICATION_INSTALLED,
          params: { key: this.$route.params.id },
        })
      }
    } else if (this.$route.query.sdk) {
      this[APP_STORE.ACTIONS.GET_SDK](this.$route.query.sdk)

      this.loading = true
      this.appActive = await callApi({
        requestData: API.appStore.getApp,
        params: {
          key: this.$route.params.id,
          sdk: this.sdk,
        },
      })
      this.$emit("appChanged", this.appActive.name)
      this.loading = false

      await this.$router.push({
        name: ROUTES.APPLICATION_INSTALLED,
        params: { key: this.$route.params.id },
      })
    } else {
      await this.$router.push({ name: ROUTES.OVERVIEW })
    }
  },
  beforeDestroy() {
    this.$emit("appChanged", null)
  },
}
</script>
<style scoped lang="scss">
.activation-label {
  width: 12ch;
}
</style>
