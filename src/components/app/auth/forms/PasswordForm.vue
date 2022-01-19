<template>
  <v-card>
    <v-card-text>
      <Logo />
    </v-card-text>
    <v-card-text>
      <div class="text-center">
        {{ email }}
      </div>
      <ValidationObserver ref="restoreForm" tag="form" @submit.prevent="submit">
        <validation-provider
          v-slot="{ errors }"
          :name="$t('setNewPassword.form.restorePassword.name')"
          :rules="fields.password.validations"
          :vid="fields.password.id"
          slim
        >
          <app-input
            v-model="form.password"
            prepend-icon="lock"
            :label="$t('setNewPassword.form.restorePassword.label')"
            input-type="password"
            :error-messages="errors"
          />
        </validation-provider>
        <validation-provider
          v-slot="{ errors }"
          :name="$t('setNewPassword.form.confirm.name')"
          :rules="fields.confirm.validations"
          slim
        >
          <app-input
            v-model="form.confirm"
            prepend-icon="lock"
            :label="$t('setNewPassword.form.confirm.label')"
            input-type="password"
            :error-messages="errors"
          />
        </validation-provider>
        <div class="text-right">
          <app-button
            :is-sending="isSending"
            :button-title="$t('button.change')"
            :sending-title="$t('button.sending.saving')"
            :on-click="submit"
            :flat="false"
          />
        </div>
      </ValidationObserver>
    </v-card-text>
  </v-card>
</template>

<script>
import { ROUTES } from '@/services/enums/routerEnums'
import FormMixin from '../../../commons/mixins/FormMixin'
import Logo from '@/components/commons/logo/Logo'
import AppButton from '@/components/commons/button/AppButton'
import AppInput from '@/components/commons/input/AppInput'

export default {
  name: 'PasswordForm',
  components: { AppInput, AppButton, Logo },
  mixins: [FormMixin],
  props: {
    email: {
      type: String,
      required: true,
    },
  },
  data() {
    return {
      ROUTES: ROUTES,
      form: {
        password: null,
        confirm: null,
      },
      fields: {
        password: {
          id: 'password',
          validations: {
            required: true,
          },
        },
        confirm: {
          id: 'confirm',
          validations: {
            required: true,
            passwordConfirm: 'password',
          },
        },
      },
    }
  },
  methods: {
    async submit() {
      const isValid = await this.$refs.restoreForm.validate()
      if (isValid !== true) {
        return
      }
      this.onSubmit(this.form)
    },
  },
}
</script>
