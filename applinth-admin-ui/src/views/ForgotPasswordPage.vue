<template>
  <LoginLayout>
    <template #title> Zapomenuté heslo </template>
    <p class="subtitle-2 color-main-text mb-6">
      Zadejte email na který odešleme link pro změnu hesla.
    </p>
    <ValidationObserver v-slot="{ handleSubmit }">
      <v-form class="form mb-8" @submit.prevent="handleSubmit(submit)">
        <TextField
          label="Email"
          type="email"
          rules="required|email"
          name="email"
          big-label
          autofocus
          v-model="email"
        />
        <div class="text-right">
          <Button
            type="submit"
            color="primary"
            no-text-transform
            large
            block
            class="button"
            >Odeslat</Button
          >
        </div>
      </v-form>
    </ValidationObserver>
  </LoginLayout>
</template>

<script lang="ts">
import Button from "../components/commons/inputsAndControls/Button.vue";
import LoginLayout from "../components/commons/layouts/LoginLayout.vue";
import TextField from "../components/commons/inputsAndControls/TextField.vue";
import { Component, Vue } from "vue-property-decorator";
import { ValidationObserver } from "vee-validate";
import { apiClient } from "../utils/apiClient";
import { api } from "../api";
import {
  ResetPasswordMutation,
  ResetPasswordMutationVariables,
} from "../types/gqlGeneratedPublic";
import { Routes } from "../enums";

@Component({
  components: {
    Button,
    LoginLayout,
    TextField,
    ValidationObserver,
  },
})
export default class ForgotPasswordPage extends Vue {
  email = "";

  async submit(): Promise<void> {
    const { data } = await apiClient.callGraphqlPublic<
      ResetPasswordMutation,
      ResetPasswordMutationVariables
    >({
      ...api.auth.resetPassword,
      variables: {
        input: { username: this.email },
      },
    });

    if (data?.resetPassword) {
      this.$router.push({ name: Routes.ResetPassword });
    }
  }
}
</script>

<style lang="scss" scoped>
.button {
  font-size: 20px;
  padding: 10px;
}
</style>
