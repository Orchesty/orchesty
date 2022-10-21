<template>
  <ValidationObserver ref="restoreForm" tag="form" @submit.prevent="submit">
    <validation-provider
      v-slot="{ errors }"
      :name="$t('form.password')"
      :rules="fields.password.validations"
      :vid="fields.password.id"
      slim
    >
      <app-input
        v-model="form.password"
        prepend-icon="key"
        :label="$t('form.password')"
        input-type="password"
        :error-messages="errors"
      />
    </validation-provider>
    <validation-provider
      v-slot="{ errors }"
      :name="$t('form.confirmPassword')"
      :rules="fields.confirm.validations"
      slim
    >
      <app-input
        v-model="form.confirm"
        prepend-icon="mdi-key-change"
        :label="$t('form.confirmPassword')"
        input-type="password"
        :error-messages="errors"
      />
    </validation-provider>
    <div class="mt-2 text-right">
      <app-button :is-sending="isSending" button-title="Set" :sending-title="$t('button.set')" :on-click="submit" />
    </div>
  </ValidationObserver>
</template>

<script>
import { ROUTES } from '@/services/enums/routerEnums'
import FormMixin from '../../../../services/mixins/FormMixin'
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
  mounted() {
    this.$refs.restoreForm.reset()
  },
}
</script>
