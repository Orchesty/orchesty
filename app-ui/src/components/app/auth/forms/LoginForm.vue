<template>
  <auth-split-layout>
    <template #heading>{{ $t("page.heading.loginToTheWorkspace") }}</template>
    <template #form>
      <ValidationObserver
        ref="loginForm"
        class="text-right login-form"
        tag="form"
        @submit.prevent="submit"
        @keydown.enter="submit"
      >
        <validation-provider
          v-slot="{ errors }"
          :name="$t('form.email')"
          :rules="fields.email.validations"
          slim
        >
          <app-input
            id="loginEmail"
            v-model="form.email"
            dense
            prepend-icon="mdi-account-circle"
            :label="$t('form.email')"
            type="text"
            :name="fields.email.id"
            autocomplete="username"
            :error-messages="errors"
            input-type="email"
          />
        </validation-provider>
        <validation-provider
          v-slot="{ errors }"
          :name="$t('form.password')"
          :rules="fields.password.validations"
          slim
        >
          <app-input
            id="loginPassword"
            v-model="form.password"
            dense
            prepend-icon="key"
            :label="$t('form.password')"
            input-type="password"
            :name="fields.password.id"
            autocomplete="current-password"
            :error-messages="errors"
          />
        </validation-provider>
        <router-link :to="{ name: ROUTES.FORGOT_PASSWORD }">
          <span class="caption"> {{ $t("navigation.forgotPassword") }} </span>
        </router-link>
        <div class="mt-5">
          <app-button
            :is-sending="isSending"
            :button-title="$t('button.login')"
            :sending-title="$t('button.sending.login')"
            :on-click="submit"
          />
        </div>
      </ValidationObserver>
    </template>
  </auth-split-layout>
</template>

<script>
import { ROUTES } from "@/services/enums/routerEnums"
import FormMixin from "@/services/mixins/FormMixin"
import { mapGetters } from "vuex"
import { REQUESTS_STATE } from "@/store/modules/api/types"
import { API } from "@/api"
import AppButton from "@/components/commons/button/AppButton"
import AppInput from "@/components/commons/input/AppInput"
import AuthSplitLayout from "@/components/app/auth/layout/AuthSplitLayout"

export default {
  name: "LoginForm",
  components: { AuthSplitLayout, AppInput, AppButton },
  computed: {
    ...mapGetters(REQUESTS_STATE.NAMESPACE, [REQUESTS_STATE.GETTERS.GET_STATE]),
    state() {
      return this[REQUESTS_STATE.GETTERS.GET_STATE]([API.auth.login.id])
    },
  },
  mixins: [FormMixin],
  data() {
    return {
      ROUTES: ROUTES,
      form: {
        email: null,
        password: null,
      },
      fields: {
        email: {
          id: "email",
          validations: {
            required: true,
            email: true,
          },
        },
        password: {
          id: "password",
          validations: {
            required: true,
          },
        },
      },
    }
  },
  methods: {
    async submit() {
      const isValid = await this.$refs.loginForm.validate()
      if (!isValid) {
        return
      }

      this.onSubmit(this.form)
    },
  },
  mounted() {
    this.$refs.loginForm.reset()
  },
}
</script>
<style lang="scss" scoped>
.login-form::v-deep .v-text-field--outlined.v-input--dense .v-label {
  transform: translateY(-16px) scale(0.75);
  background-color: var(--v-white-base) !important;
  padding-left: 10px;
  padding-right: 10px;
}
</style>
