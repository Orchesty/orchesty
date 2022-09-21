<template>
  <CenteredLayout>
    <AuthSplitLayout>
      <template #heading> {{ $t("forgotPasswordPage.header") }}</template>
      <template #form>
        <p>
          {{ $t("forgotPasswordPage.body") }}
        </p>
        <ValidationObserver
          tag="form"
          @submit.prevent="submit"
          @keydown.enter="submit"
          v-slot="{ invalid }"
        >
          <TextField
            :label="$t('formLabels.tenantId')"
            type="text"
            rules="required"
            :name="$t('formLabels.tenantId')"
            autofocus
            v-model="formData.tenantId"
          />
          <TextField
            :label="$t('formLabels.email')"
            type="email"
            rules="required|email"
            :name="$t('formLabels.email')"
            v-model="formData.email"
          />
          <div class="text-right">
            <Button type="submit" :disabled="invalid">{{
              $t("button.send")
            }}</Button>
          </div>
        </ValidationObserver>
      </template>
    </AuthSplitLayout>
  </CenteredLayout>
</template>

<script lang="ts">
import Button from "../components/commons/inputsAndControls/Button.vue";
import TextField from "../components/commons/inputsAndControls/TextField.vue";
import { Component, Vue } from "vue-property-decorator";
import { ValidationObserver } from "vee-validate";
import AuthSplitLayout from "@/components/commons/layouts/AuthSplitLayout.vue";
import CenteredLayout from "@/components/commons/layouts/CenteredLayout.vue";
import { Action } from "vuex-class";
import { AuthActions, authNamespace } from "@/store/modules/auth";
import { TResetPasswordForm } from "@/components/auth/types";

@Component({
  components: {
    CenteredLayout,
    AuthSplitLayout,
    Button,
    TextField,
    ValidationObserver,
  },
})
export default class ForgotPasswordPage extends Vue {
  @Action(`${authNamespace}/${AuthActions.SendResetPasswordLink}`)
  private sendResetPasswordLink!: (
    payload: TResetPasswordForm
  ) => Promise<boolean>;

  formData: TResetPasswordForm = {
    email: "",
    tenantId: "",
  };

  async submit(): Promise<void> {
    if (await this.sendResetPasswordLink(this.formData)) {
      this.formData.email = "";
      this.formData.tenantId = "";
    }
  }
}
</script>

<style lang="scss" scoped></style>
