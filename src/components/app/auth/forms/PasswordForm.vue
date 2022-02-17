<template>
  <ValidationObserver ref="restoreForm" tag="form" @submit.prevent="submit">
    <validation-provider
      v-slot="{ errors }"
      :name="$t('setNewPassword.form.password.name')"
      :rules="fields.password.validations"
      :vid="fields.password.id"
      slim
    >
      <app-input
        v-model="form.password"
        prepend-icon="lock"
        :label="$t('setNewPassword.form.password.label')"
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
    <div>
      <app-button
        :height="44"
        :custom-style="{ width: '100%' }"
        :is-sending="isSending"
        button-title="Set new password"
        :sending-title="$t('button.sending.saving')"
        :on-click="submit"
        :flat="false"
      />
    </div>
  </ValidationObserver>
</template>

<script>
import { ROUTES } from '@/services/enums/routerEnums'
import FormMixin from '../../../commons/mixins/FormMixin'
import AppButton from '@/components/commons/button/AppButton'
import AppInput from '@/components/commons/input/AppInput'

export default {
  name: 'PasswordForm',
  components: { AppInput, AppButton },
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
