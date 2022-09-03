<template>
  <CenteredLayout>
    <AuthSplitLayout>
      <template #heading>
        <Heading>{{ $t("newPasswordPage.header") }}</Heading>
      </template>
      <template #form>
        <NewPassword />
      </template>
    </AuthSplitLayout>
  </CenteredLayout>
</template>

<script lang="ts">
import { Component, Vue } from "vue-property-decorator";
import NewPassword from "../components/auth/NewPassword.vue";
import CenteredLayout from "@/components/commons/layouts/CenteredLayout.vue";
import AuthSplitLayout from "@/components/commons/layouts/AuthSplitLayout.vue";
import Heading from "@/components/Heading.vue";

@Component({
  components: {
    Heading,
    AuthSplitLayout,
    CenteredLayout,
    NewPassword,
  },
})
export default class NewPasswordPage extends Vue {}
</script>

<style lang="scss" scoped></style>
