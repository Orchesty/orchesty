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
          <v-text-field
            v-model="form.password"
            prepend-icon="lock"
            :label="$t('setNewPassword.form.restorePassword.label')"
            type="password"
            :error-messages="errors[0]"
          />
        </validation-provider>
        <validation-provider
          v-slot="{ errors }"
          :name="$t('setNewPassword.form.confirm.name')"
          :rules="fields.confirm.validations"
          slim
        >
          <v-text-field
            v-model="form.confirm"
            prepend-icon="lock"
            :label="$t('setNewPassword.form.confirm.label')"
            type="password"
            :error-messages="errors[0]"
          />
        </validation-provider>
        <div class="text-right">
          <sending-button
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
import SendingButton from '@/components/commons/button/AppButton'

export default {
  name: 'PasswordForm',
  components: { SendingButton, Logo },
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
