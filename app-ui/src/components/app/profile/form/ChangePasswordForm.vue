<template>
  <ValidationObserver ref="form" tag="form" slim @submit.prevent="submit">
    <v-row>
      <v-col cols="12">
        <h3 class="title font-weight-bold">{{ $t('profile.changePassword.title') }}</h3>
      </v-col>
    </v-row>
    <v-row dense>
      <v-col cols="12">
        <v-row dense>
          <v-col cols="12">
            <validation-provider
              v-slot="{ errors }"
              :name="$t('profile.changePassword.form.current-password.name')"
              :vid="fields.current.id"
              :rules="fields.current.validations"
              slim
            >
              <app-input
                :ref="fields.current.id"
                v-model="form.current"
                dense
                :label="$t('profile.changePassword.form.current-password.label')"
                input-type="password"
                outlined
                :error-messages="errors"
              />
            </validation-provider>
          </v-col>
        </v-row>
        <v-row dense>
          <v-col cols="12">
            <validation-provider
              v-slot="{ errors }"
              :name="$t('profile.changePassword.form.password.name')"
              :vid="fields.password.id"
              :rules="fields.password.validations"
              slim
            >
              <app-input
                :ref="fields.password.id"
                v-model="form.password"
                dense
                outlined
                :label="$t('profile.changePassword.form.password.label')"
                input-type="password"
                :error-messages="errors"
              />
            </validation-provider>
          </v-col>
        </v-row>
        <v-row dense>
          <v-col cols="12">
            <validation-provider
              v-slot="{ errors }"
              :name="$t('profile.changePassword.form.confirm.name')"
              :rules="fields.confirm.validations"
              slim
            >
              <app-input
                v-model="form.confirm"
                dense
                :label="$t('profile.changePassword.form.confirm.label')"
                input-type="password"
                outlined
                :error-messages="errors"
              />
            </validation-provider>
          </v-col>
        </v-row>
        <v-row dense>
          <v-col cols="12" class="text-right">
            <app-button
              :is-sending="isSending"
              :button-title="$t('button.save')"
              :sending-title="$t('button.sending.saving')"
              :on-click="submit"
              :flat="false"
            />
          </v-col>
        </v-row>
      </v-col>
    </v-row>
  </ValidationObserver>
</template>

<script>
import { ROUTES } from '@/services/enums/routerEnums'
import FormMixin from '../../../commons/mixins/FormMixin'
import { AUTH } from '@/store/modules/auth/types'
import { mapActions, mapGetters } from 'vuex'
import { REQUESTS_STATE } from '@/store/modules/api/types'
import { API } from '@/api'
import AppButton from '@/components/commons/button/AppButton'
import AppInput from '@/components/commons/input/AppInput'

export default {
  name: 'ChangePasswordForm',
  components: { AppInput, AppButton },
  mixins: [FormMixin],
  data() {
    return {
      ROUTES: ROUTES,
      form: {
        ...this.initForm(),
      },
      fields: {
        current: {
          id: 'current',
          validations: {
            required: true,
          },
        },
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
  computed: {
    ...mapGetters(REQUESTS_STATE.NAMESPACE, [REQUESTS_STATE.GETTERS.GET_STATE]),
    state() {
      return this[REQUESTS_STATE.GETTERS.GET_STATE]([API.auth.changePassword.id])
    },
  },
  methods: {
    ...mapActions(AUTH.NAMESPACE, [AUTH.ACTIONS.CHANGE_PASSWORD_REQUEST]),

    async submit() {
      const isValid = await this.$refs.form.validate()
      if (isValid !== true) {
        return
      }
      const res = this[AUTH.ACTIONS.CHANGE_PASSWORD_REQUEST]({
        password: this.form.password,
        old_password: this.form.current,
      })
      if (res) {
        this.reset()
      }
    },
    initForm() {
      return {
        current: null,
        password: null,
        confirm: null,
      }
    },
    reset() {
      this.form = this.initForm()
      this.$refs.form.reset()
    },
  },
  mounted() {
    this.$refs.form.reset()
  },
}
</script>