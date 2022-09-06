<template>
  <CenteredLayout>
    <AuthSplitLayout>
      <template #heading>
        {{ $t("passwordChangedPage.header") }}
      </template>
      <template #form>
        <p>{{ $t("passwordChangedPage.body") }}</p>
        <Button :to="{ name: Routes.Login }"> {{ $t("button.login") }} </Button>
      </template>
    </AuthSplitLayout>
  </CenteredLayout>
</template>

<script lang="ts">
import { Component, Vue } from "vue-property-decorator";
import Button from "../components/commons/inputsAndControls/Button.vue";
import { Routes } from "../enums";
import CenteredLayout from "@/components/commons/layouts/CenteredLayout.vue";
import AuthSplitLayout from "@/components/commons/layouts/AuthSplitLayout.vue";

@Component({
  components: {
    AuthSplitLayout,
    CenteredLayout,
    Button,
  },
})
export default class ChangedPasswordPage extends Vue {
  Routes = Routes;
}
</script>
