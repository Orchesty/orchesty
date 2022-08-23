<template>
  <div v-if="app">
    <v-row>
      <v-col>
        <navigation-item
          :text="navigationItem.text"
          :icon="navigationItem.icon"
          :to="navigationItem.to"
          :color="navigationItem.color"
        />
      </v-col>
    </v-row>
    <v-row>
      <v-col>
        <v-img max-width="150" contain :src="appLogo" />
      </v-col>
    </v-row>
    <v-row>
      <v-col class="d-flex justify-space-between flex-column">
        <h1 class="headline font-weight-bold">{{ app.name }}</h1>
        <p class="mt-4">{{ app.description }}</p>
        <div>
          <base-button
            :button-title="$t('button.install')"
            :on-click="install"
          />
        </div>
      </v-col>
    </v-row>
  </div>
</template>

<script>
import BaseButton from '@/components/commons/BaseButton'
import { callApi } from '@/utils/apiFetch'
import { API } from '@/api'
import NavigationItem from '@/components/commons/NavigationItem'
import { ROUTES } from '@/router/routes'
export default {
  name: 'AppAvailableDetailPage',
  components: { NavigationItem, BaseButton },
  computed: {
    appLogo() {
      return this.app.logo
        ? this.app.logo
        : require('@/assets/svg/app-store-item-logo-placeholder.svg')
    },
  },
  data() {
    return {
      app: null,
      navigationItem: {
        to: ROUTES.APPLICATIONS,
        icon: 'mdi-arrow-left-circle',
        text: 'navigation.link.backToTheApplications',
        color: 'primary',
      },
    }
  },
  methods: {
    async install() {
      await callApi({
        requestData: API.appStore.installApp,
        params: { key: this.$route.params.id },
      })
      await this.$router.push({
        name: ROUTES.APPLICATION_INSTALLED,
        params: { id: this.$route.params.id },
      })
    },
  },
  async created() {
    this.app = await callApi({
      requestData: API.appStore.getAppPreview,
      params: { key: this.$route.params.id },
    })
    this.$emit('appChanged', this.app.name)
  },
  beforeDestroy() {
    this.$emit('appChanged', null)
  },
}
</script>
