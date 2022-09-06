<template>
  <CenteredLayout>
    <AuthSplitLayout>
      <template #heading> {{ $t("loginPage.header") }}</template>
      <template #form>
        <LoginHandler />
      </template>
    </AuthSplitLayout>
  </CenteredLayout>
</template>

<script lang="ts">
import { Component, Vue } from "vue-property-decorator";
import LoginHandler from "../components/auth/LoginHandler.vue";
import AuthSplitLayout from "@/components/commons/layouts/AuthSplitLayout.vue";
import CenteredLayout from "@/components/commons/layouts/CenteredLayout.vue";

@Component({
  components: {
    CenteredLayout,
    AuthSplitLayout,
    LoginHandler,
  },
})
export default class LoginPage extends Vue {}
</script>

<style lang="scss" scoped></style>
