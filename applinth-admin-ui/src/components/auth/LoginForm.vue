<template>
  <ValidationObserver v-slot="{ handleSubmit }">
    <v-form class="mb-8" @submit.prevent="handleSubmit(submit)">
      <TextField
        label="Tenant ID"
        big-label
        name="Tenant"
        v-model="form.tenant"
        type="text"
      />
      <TextField
        label="Email"
        big-label
        :name="$t('login.form.email.name')"
        v-model="form.email"
        type="email"
        :rules="rules.email"
      />
      <TextField
        label="Heslo"
        big-label
        :name="$t('login.form.password.name')"
        :rules="rules.password"
        v-model="form.password"
        type="password"
        autocomplete="current-password"
      />
      <div class="text-right mb-4">
        <router-link :to="{ name: Routes.ForgotPassword }" class="link">
          Zapomenuté heslo?
        </router-link>
      </div>
      <Button type="submit" color="primary" :on-click="submit">
        {{ $t("button.login") }}
      </Button>
    </v-form>
  </ValidationObserver>
</template>

<script lang="ts">
import { Component, Prop, Vue } from "vue-property-decorator";
import { TLoginForm, TLoginRules } from "./types";
import { ValidationObserver, ValidationProvider } from "vee-validate";
import TextField from "../commons/inputsAndControls/TextField.vue";
import Logo from "../commons/Logo.vue";
import Button from "../commons/inputsAndControls/Button.vue";
import { Routes } from "../../enums/Routes";

@Component({
  components: {
    Logo,
    ValidationObserver,
    ValidationProvider,
    TextField,
    Button,
  },
})
export default class LoginForm extends Vue {
  Routes = Routes;

  @Prop({ required: true, type: Function })
  private onSubmit!: (payload: TLoginForm) => Promise<boolean>;

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
    const result = await this.onSubmit(this.form);
    if (result) {
      if (this.$route.query?.redirect) {
        this.$router.push(this.$route.query.redirect as string);
      } else {
        this.$router.push("/");
      }
    }
  }
}
</script>

<style lang="scss" scoped>
.link {
  text-decoration: none;

  &:hover {
    text-decoration: underline;
  }
}
</style>
