<template>
  <content-layout :is-sending="state.isSending">
    <v-alert v-if="email === null" type="error">
      {{ $t('registrationConfirm.notValidToken') }}
    </v-alert>
    <set-new-password v-else :email="email" :on-submit="submit" :is-sending="setPasswordState.isSending" />
  </content-layout>
</template>

<script>
import { REQUESTS_STATE } from '../../../../store/modules/api/types'
import { API } from '../../../../api'
import { mapActions, mapGetters } from 'vuex'
import { AUTH } from '../../../../store/modules/auth/types'
import SetNewPassword from '../restorePassword/SetNewPassword'
import ContentLayout from '../../../layout/ContentLayout'

export default {
  components: { SetNewPassword, ContentLayout },
  name: 'RegistrationConfirmFormHandler',
  async created() {
    this.email = await this[AUTH.ACTIONS.CHECK_REGISTER_TOKEN_REQUEST]({ token: this.$route.params.token })
  },
  computed: {
    ...mapGetters(REQUESTS_STATE.NAMESPACE, [REQUESTS_STATE.GETTERS.GET_STATE]),
    state() {
      return this[REQUESTS_STATE.GETTERS.GET_STATE]([API.auth.checkRegisterToken.id])
    },
    setPasswordState() {
      return this[REQUESTS_STATE.GETTERS.GET_STATE]([API.auth.setPassword.id])
    },
  },
  data() {
    return {
      email: null,
    }
  },
  methods: {
    ...mapActions(AUTH.NAMESPACE, [AUTH.ACTIONS.CHECK_REGISTER_TOKEN_REQUEST, AUTH.ACTIONS.SET_PASSWORD_REQUEST]),
    submit(values) {
      this[AUTH.ACTIONS.SET_PASSWORD_REQUEST]({
        token: this.$route.params.token,
        password: values.password,
      })
    },
  },
}
</script>
