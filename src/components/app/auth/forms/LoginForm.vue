<template>
  <v-card elevation="3" rounded="lg">
    <v-row>
      <v-col cols="12" lg="6" class="d-flex">
        <v-card-text class="my-auto mx-5">
          <ValidationObserver ref="loginForm" tag="form" @submit.prevent="submit">
            <validation-provider
              v-slot="{ errors }"
              :name="$t('login.form.email.name')"
              :rules="fields.email.validations"
              slim
            >
              <v-text-field
                v-model="form.email"
                prepend-icon="person"
                :label="$t('login.form.email.label')"
                type="text"
                :name="fields.email.id"
                :error-messages="errors[0]"
                autofocus
              />
            </validation-provider>
            <validation-provider
              v-slot="{ errors }"
              :name="$t('login.form.password.name')"
              :rules="fields.password.validations"
              slim
            >
              <v-text-field
                v-model="form.password"
                prepend-icon="lock"
                :label="$t('login.form.password.label')"
                type="password"
                :error-messages="errors[0]"
              />
            </validation-provider>
            <router-link :to="{ name: ROUTES.FORGOT_PASSWORD }">
              <span class="ml-8"> {{ $t('login.forgot_link') }} </span>
            </router-link>
            <div class="text-right">
              <sending-button
                :is-sending="isSending"
                :button-title="$t('login.form.submit.label')"
                :sending-title="$t('button.sending.login')"
                :on-click="submit"
                :flat="false"
              />
            </div>
          </ValidationObserver>
        </v-card-text>
      </v-col>
      <v-col cols="0" lg="6" class="login-image d-none d-lg-block">
        <img alt="hero_image" src="@/assets/svg/login_illustration.svg" />
      </v-col>
    </v-row>
  </v-card>
</template>

<script>
import { ROUTES } from '../../../../services/enums/routerEnums'
import FormMixin from '@/components/commons/mixins/FormMixin'
import SendingButton from '@/components/commons/button/SendingButton'
import { mapGetters } from 'vuex'
import { REQUESTS_STATE } from '@/store/modules/api/types'
import { API } from '@/api'

export default {
  name: 'LoginForm',
  components: { SendingButton },
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
}
</script>
<style lang="scss" scoped>
.login-image {
  padding: 0 !important;
  img {
    border-top-right-radius: 8px;
    border-bottom-right-radius: 8px;
    height: 100%;
    width: 100%;
    object-fit: cover;
  }
}
</style>
