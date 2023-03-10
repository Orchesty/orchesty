<template>
  <auth-split-layout>
    <template #heading> {{ $t("page.heading.forgotPassword") }} </template>
    <template #form>
      <p>
        {{ $t("page.text.forgotPassword") }}
      </p>
      <ValidationObserver ref="forgotForm" tag="form" @submit.prevent="submit">
        <validation-provider
          v-slot="{ errors }"
          :name="$t('form.email')"
          :rules="fields.email.validations"
          slim
        >
          <app-input
            v-model="form.email"
            prepend-icon="email"
            :label="$t('form.email')"
            type="text"
            :name="fields.email.id"
            :error-messages="errors"
          />
        </validation-provider>
        <div class="text-right">
          <app-button
            :is-sending="isSending"
            :button-title="$t('button.send')"
            :sending-title="$t('button.sending.sending')"
            :on-click="submit"
          />
        </div>
      </ValidationObserver>
    </template>
  </auth-split-layout>
</template>

<script>
import { ROUTES } from "@/services/enums/routerEnums"
import FormMixin from "../../../../services/mixins/FormMixin"
import AppButton from "@/components/commons/button/AppButton"
import AppInput from "@/components/commons/input/AppInput"
import AuthSplitLayout from "@/components/app/auth/layout/AuthSplitLayout"

export default {
  name: "ForgotPasswordForm",
  components: { AuthSplitLayout, AppInput, AppButton },
  mixins: [FormMixin],
  data() {
    return {
      ROUTES: ROUTES,
      form: {
        ...this.initForm(),
      },
      fields: {
        email: {
          id: "email",
          validations: {
            required: true,
            email: true,
          },
        },
      },
    }
  },
  methods: {
    async submit() {
      const isValid = await this.$refs.forgotForm.validate()
      if (!isValid) {
        return
      }

      const res = await this.onSubmit(this.form)

      if (res) {
        this.reset()
      }
    },
    initForm() {
      this.$nextTick(() => {
        this.$refs.forgotForm.reset()
      })
      return {
        email: null,
      }
    },
    reset() {
      this.form = this.initForm()
      this.$refs.forgotForm.reset()
    },
  },
}
</script>
