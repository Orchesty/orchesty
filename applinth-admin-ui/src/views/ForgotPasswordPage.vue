<template>
  <CenteredLayout>
    <AuthSplitLayout>
      <template #heading> {{ $t("forgotPasswordPage.header") }}</template>
      <template #form>
        <p>
          {{ $t("forgotPasswordPage.body") }}
        </p>
        <ValidationObserver v-slot="{ handleSubmit }">
          <v-form class="form mb-8" @submit.prevent="handleSubmit(submit)">
            <TextField
              :label="$t('formLabels.email')"
              type="email"
              rules="required|email"
              :name="$t('formLabels.email')"
              big-label
              autofocus
              v-model="email"
            />
            <div class="text-right">
              <Button type="submit">{{ $t("button.send") }}</Button>
            </div>
          </v-form>
        </ValidationObserver>
      </template>
    </AuthSplitLayout>
  </CenteredLayout>
</template>

<script lang="ts">
import Button from "../components/commons/inputsAndControls/Button.vue";
import LoginLayout from "../components/commons/layouts/LoginLayout.vue";
import TextField from "../components/commons/inputsAndControls/TextField.vue";
import { Component, Vue } from "vue-property-decorator";
import { ValidationObserver } from "vee-validate";
import AuthSplitLayout from "@/components/commons/layouts/AuthSplitLayout.vue";
import CenteredLayout from "@/components/commons/layouts/CenteredLayout.vue";

@Component({
  components: {
    CenteredLayout,
    AuthSplitLayout,
    Button,
    LoginLayout,
    TextField,
    ValidationObserver,
  },
})
export default class ForgotPasswordPage extends Vue {
  email = "";

  async submit(): Promise<void> {
    // TODO implement using firebase
    // const { data } = await apiClient.callGraphqlPublic<
    //   ResetPasswordMutation,
    //   ResetPasswordMutationVariables
    // >({
    //   ...api.auth.resetPassword,
    //   variables: {
    //     input: { username: this.email },
    //   },
    // });
    // if (data?.resetPassword) {
    //   this.$router.push({ name: Routes.ResetPassword });
    // }
  }
}
</script>

<style lang="scss" scoped>
.button {
  font-size: 20px;
  padding: 10px;
}
</style>
