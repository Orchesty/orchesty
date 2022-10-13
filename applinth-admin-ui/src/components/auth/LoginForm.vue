<template>
  <ValidationObserver
    tag="form"
    @submit.prevent="submit"
    @keydown.enter="submit"
    ref="formRef"
    autocomplete="on"
  >
    <TextField
      :label="$t('formLabels.tenantId')"
      name="tenantId"
      v-model="form.tenant"
      type="text"
      autofocus
      id="loginTenant"
    />
    <TextField
      :label="$t('formLabels.email')"
      name="email"
      v-model="form.email"
      type="email"
      :rules="rules.email"
      autocomplete="username"
      persistent-placeholder
      placeholder=" "
      id="loginEmail"
    />
    <TextField
      :label="$t('formLabels.password')"
      name="password"
      :rules="rules.password"
      v-model="form.password"
      type="password"
      autocomplete="current-password"
      persistent-placeholder
      placeholder=" "
      id="loginPassword"
    />
    <div class="text-right mb-4">
      <router-link :to="{ name: Routes.ForgotPassword }" class="link">
        {{ $t("loginPage.forgotPasswordLink") }}
      </router-link>
    </div>
    <div class="text-right">
      <Button :loading="loading" type="submit">
        {{ $t("button.login") }}
      </Button>
    </div>
  </ValidationObserver>
</template>

<script lang="ts">
import { Component, Prop, Ref, Vue } from "vue-property-decorator";
import { TLoginForm, TLoginRules } from "./types";
import { ValidationObserver } from "vee-validate";
import TextField from "../commons/inputsAndControls/TextField.vue";
import Logo from "../commons/layouts/Logo.vue";
import Button from "../commons/inputsAndControls/Button.vue";
import { Routes } from "../../enums/Routes";

@Component({
  components: {
    Logo,
    ValidationObserver,
    TextField,
    Button,
  },
})
export default class LoginForm extends Vue {
  Routes = Routes;

  @Prop({ required: true, type: Function })
  private onSubmit!: (payload: TLoginForm) => Promise<boolean>;

  @Ref()
  readonly formRef!: InstanceType<typeof ValidationObserver>;

  loading = false;

  form: TLoginForm = {
    email: "",
    password: "",
    tenant: "",
  };

  rules: TLoginRules = {
    email: {
      required: true,
      email: true,
    },
    password: {
      required: true,
    },
  };

  async submit(): Promise<void> {
    this.loading = true;
    const isValid = await this.formRef.validate();

    if (isValid) {
      const result = await this.onSubmit(this.form);
      if (result) {
        if (this.$route.query?.redirect) {
          this.$router.push(this.$route.query.redirect as string);
        } else {
          this.$router.push("/");
        }
      }
    }

    this.loading = false;
  }
}
</script>

<style lang="scss" scoped></style>
