<template>
  <div v-if="rootApp">
    <AppForm :active-app="rootApp" />
  </div>
</template>

<script>
import { callApi } from "@/utils/apiFetch"
import { API } from "@/api"
import { ROUTES } from "@/router/routes"
import { config } from "@/config"
import AppForm from "@/components/applications/AppForm.vue"
import { APP_STORE } from "@/store/appStore/types"
import { mapGetters } from "vuex"

export default {
  name: "SettingsPage",
  components: {
    AppForm,
  },
  data() {
    return {
      hasOauthAuthorization: false,
      rootApp: null,
      navigationItem: {
        to: ROUTES.APPLICATIONS,
        icon: "mdi-arrow-left-circle",
        text: "navigation.link.backToTheApplications",
        color: "primary",
      },
    }
  },
  computed: {
    ...mapGetters(APP_STORE.NAMESPACE, {
      sdk: APP_STORE.GETTERS.GET_SDK,
    }),
  },
  methods: {
    async authorizeApp() {
      const authorizeURL = new URL(
        API.authorize.getAuthorizationSettingsLink(this.sdk),
        config.backend.apiBaseUrl
      )
      authorizeURL.searchParams.append("redirect_url", window.location.href)
      window.open(authorizeURL.href, "_blank").focus()
    },

    hasOauth() {
      this.hasOauthAuthorization =
        this.rootApp.authorization_type.startsWith("oauth")
    },

    hasLogo(app) {
      return app?.logo ? app.logo : ""
    },
  },
  watch: {
    rootApp: {
      immediate: true,
      handler() {
        if (this.rootApp) {
          this.hasOauth()
        }
      },
    },
  },
  async created() {
    this.rootApp = await callApi({
      requestData: API.settings.getSettings,
      params: {
        sdk: this.sdk,
      },
    })
  },
}
</script>
<style scoped lang="scss"></style>
