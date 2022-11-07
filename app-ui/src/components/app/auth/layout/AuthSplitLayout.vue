<template>
  <v-card elevation="3" rounded="lg">
    <v-row dense class="px-1">
      <v-col cols="12" lg="0" class="login-image d-flex d-lg-none">
        <img alt="hero_image" src="@/assets/svg/login-screen-banner.svg" />
      </v-col>
    </v-row>
    <v-row dense>
      <v-col cols="12" lg="6" class="d-flex align-center px-xl-11">
        <v-container>
          <v-row dense>
            <v-col class="text-center">
              <h3 class="headline font-weight-bold">
                <slot name="heading" />
              </h3>
            </v-col>
          </v-row>
          <v-row dense>
            <v-col class="mt-5">
              <slot name="form" />
            </v-col>
          </v-row>
        </v-container>
      </v-col>
      <v-col cols="0" lg="6" class="login-image d-none d-lg-flex">
        <img alt="hero_image" src="@/assets/svg/login-screen-banner.svg" />
      </v-col>
    </v-row>
  </v-card>
</template>

<script>
export default {
  name: "AuthSplitLayout",
  props: {
    disableHeader: {
      type: Boolean,
      default: false,
    },
  },
}
</script>

<style lang="scss" scoped>
.login-image {
  padding: 0 !important;

  img {
    border-top-right-radius: 8px;
    border-bottom-right-radius: 8px;
    border-top-left-radius: 0;
    height: 100%;
    width: 100%;
    max-height: unset;
    object-fit: cover;

    @media #{map-get($display-breakpoints, 'md-and-down')} {
      border-top-right-radius: 8px;
      border-top-left-radius: 8px;
      border-bottom-right-radius: 0;
      height: 100%;
      width: 100%;
      max-height: 200px;
      object-fit: cover;
    }
  }
}
</style>
