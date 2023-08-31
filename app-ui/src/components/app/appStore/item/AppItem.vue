<template>
  <v-col xl="2" lg="4" md="6" sm="6" cols="12">
    <v-card rounded="lg" elevation="2" min-height="205">
      <v-container fluid>
        <v-row dense>
          <v-col cols="6" sm="5">
            <v-img max-height="70" max-width="70" contain :src="appLogo" />
          </v-col>
          <v-col
            cols="6"
            sm="7"
            class="d-flex flex-column align-end justify-start"
          >
            <slot name="buttons"></slot>
          </v-col>
        </v-row>
        <v-row dense>
          <v-col class="d-flex justify-start">
            <h3 class="title font-weight-bold text-truncate">
              {{ title }}
            </h3>
            <tooltip>
              <template #activator="{ on, attrs }">
                <v-icon
                  v-if="installed"
                  v-bind="attrs"
                  dense
                  :color="authorized ? 'success' : 'error'"
                  class="ml-3"
                  v-on="on"
                >
                  mdi-circle
                </v-icon>
              </template>
              <template #tooltip>
                <span class="text-capitalize">{{
                  authorized
                    ? $t("page.status.authorized")
                    : $t("page.status.unauthorized")
                }}</span>
              </template>
            </tooltip>
          </v-col>
        </v-row>
        <v-row dense>
          <v-col class="truncate-3-rows py-0 mb-3">
            <span>{{ description }}</span>
          </v-col>
        </v-row>
      </v-container>
    </v-card>
  </v-col>
</template>

<script>
import Tooltip from "@/components/commons/Tooltip"
export default {
  name: "AppItem",
  components: { Tooltip },
  props: {
    title: {
      type: String,
      required: true,
    },
    logo: {
      type: String,
      required: true,
    },
    description: {
      type: String,
      required: true,
    },
    authorized: {
      type: Boolean,
      default: false,
    },
    installed: {
      type: Boolean,
      required: true,
    },
  },
  computed: {
    appLogo() {
      return this.logo
        ? this.logo
        : require("@/assets/svg/app-item-placeholder.svg")
    },
  },
}
</script>

<style lang="scss" scoped>
.truncate-3-rows {
  /*
  Line clamp documentation
  https://css-tricks.com/line-clampin/
  https://caniuse.com/css-line-clamp
  */
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 3;
  display: -webkit-box;
  overflow: hidden;
}
</style>
