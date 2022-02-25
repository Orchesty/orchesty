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
          :name="$t('login.form.email.name')"
          :rules="fields.email.validations"
          slim
        >
          <app-input
            v-model="form.email"
            dense
            prepend-icon="person"
            :label="$t('login.form.email.label')"
            type="text"
            :name="fields.email.id"
            :error-messages="errors"
          />
        </validation-provider>
        <validation-provider
          v-slot="{ errors }"
          :name="$t('login.form.password.name')"
          :rules="fields.password.validations"
          slim
        >
          <app-input
            v-model="form.password"
            dense
            prepend-icon="lock"
            :label="$t('login.form.password.label')"
            input-type="password"
            :error-messages="errors"
          />
        </validation-provider>
        <router-link :to="{ name: ROUTES.FORGOT_PASSWORD }">
          <span class="caption"> {{ $t('login.forgot_link') }} </span>
        </router-link>
        <div class="mt-5">
          <app-button
            :is-sending="isSending"
            :button-title="$t('login.form.submit.label')"
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
import FormMixin from '@/components/commons/mixins/FormMixin'
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
        rememberMe: false,
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
        rememberMe: {
          id: 'rememberMe',
          validations: {},
        },
      },
    }
  },
  methods: {
    async submit() {
      if (this.state.isSending) {
        return
      }
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
