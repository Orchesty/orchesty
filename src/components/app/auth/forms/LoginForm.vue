<template>
  <auth-split-layout>
    <template #heading> Login to you workspace </template>
    <template #form>
      <ValidationObserver
        ref="loginForm"
        class="text-right"
        tag="form"
        @submit.prevent="submit"
        @keydown.enter="submit"
      >
        <validation-provider
          v-slot="{ errors }"
          :name="$t('auth.inputs.email.fieldName')"
          :rules="fields.email.validations"
          slim
        >
          <app-input
            v-model="form.email"
            dense
            prepend-icon="mdi-account-circle"
            :label="$t('auth.inputs.email.label')"
            type="text"
            :name="fields.email.id"
            :error-messages="errors"
          />
        </validation-provider>
        <validation-provider
          v-slot="{ errors }"
          :name="$t('auth.inputs.password.fieldName')"
          :rules="fields.password.validations"
          slim
        >
          <app-input
            v-model="form.password"
            dense
            prepend-icon="key"
            :label="$t('auth.inputs.password.label')"
            input-type="password"
            :error-messages="errors"
          />
        </validation-provider>
        <router-link :to="{ name: ROUTES.FORGOT_PASSWORD }">
          <span class="caption"> {{ $t('auth.links.forgotPassword') }} </span>
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
import { ROUTES } from '@/services/enums/routerEnums'
import FormMixin from '@/services/mixins/FormMixin'
import { mapGetters } from 'vuex'
import { REQUESTS_STATE } from '@/store/modules/api/types'
import { API } from '@/api'
import AppButton from '@/components/commons/button/AppButton'
import AppInput from '@/components/commons/input/AppInput'
import AuthSplitLayout from '@/components/app/auth/layout/AuthSplitLayout'

export default {
  name: 'LoginForm',
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
          id: 'email',
          validations: {
            required: true,
            email: true,
          },
        },
        password: {
          id: 'password',
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
