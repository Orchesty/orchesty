<template>
  <v-card>
    <v-card-text>
      <hanaboso-logo />
    </v-card-text>
    <v-card-text>
      <ValidationObserver ref="forgotForm" tag="form" @submit.prevent="submit">
        <validation-provider
          v-slot="{ errors }"
          :name="$t('forgotPassword.form.email.name')"
          :rules="fields.email.validations"
          slim
        >
          <v-text-field
            v-model="form.email"
            prepend-icon="person"
            :label="$t('forgotPassword.form.email.label')"
            type="text"
            :name="fields.email.id"
            :error-messages="errors[0]"
            autofocus
          />
        </validation-provider>
        <div class="text-right">
          <sending-button
            :is-sending="isSending"
            :button-title="$t('button.send')"
            :sending-title="$t('button.sending.sending')"
            :on-click="submit"
            :flat="false"
          />
        </div>
      </ValidationObserver>
    </v-card-text>
  </v-card>
</template>

<script>
import { ROUTES } from '../../../../router/routes'
import FormMixin from '../../../commons/mixins/FormMixin'
import HanabosoLogo from '../../../commons/logo/FullLogo'
import SendingButton from '@/components/commons/button/SendingButton'

export default {
  name: 'ForgotPasswordForm',
  components: { SendingButton, HanabosoLogo },
  mixins: [FormMixin],
  data() {
    return {
      ROUTES: ROUTES,
      form: {
        ...this.initForm(),
      },
      fields: {
        email: {
          id: 'email',
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
