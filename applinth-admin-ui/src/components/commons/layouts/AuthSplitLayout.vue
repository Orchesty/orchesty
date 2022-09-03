<template>
  <v-card elevation="3" rounded="lg">
    <v-row dense>
      <v-col cols="12" lg="6" class="content-wrapper">
        <Heading>
          <slot name="heading" />
        </Heading>
        <div class="content-form">
          <slot name="form" />
        </div>
      </v-col>
      <v-col
        cols="0"
        lg="6"
        class="login-image d-none d-lg-flex login-image-wrapper"
      >
        <img alt="hero_image" src="../../../assets/svg/logo.svg" />
      </v-col>
    </v-row>
  </v-card>
</template>

<script>
import { Component, Vue } from "vue-property-decorator";
import Heading from "@/components/Heading";
@Component({
  components: { Heading },
})
export default class AuthSplitLayout extends Vue {}
</script>

<style lang="scss" scoped>
.content-wrapper {
  align-items: center;
  display: flex;
  flex-direction: column;
  margin-top: 20px;
}
.content-form {
  margin: 20px;
  width: 300px;
}
.login-image-wrapper {
  background-color: $primary;
  border-top-right-radius: 8px;
  border-bottom-right-radius: 8px;
  border-top-left-radius: 0;
  display: flex;
  @media #{map-get($display-breakpoints, 'md-and-down')} {
    border-top-left-radius: 8px;
    border-bottom-right-radius: 0;
  }
}
.login-image {
  padding: 0 !important;
  img {
    width: 70%;
    margin: auto;
  }
}
</style>
