<template>
  <modal-template
    v-model="isOpen"
    :title="input.value ? $t('appItemPasswordModal.changePassword') : $t('appItemPasswordModal.setPassword')"
  >
    <template #button>
      <app-button
        :button-title="input.value ? $t('appItemPasswordModal.changePassword') : $t('appItemPasswordModal.setPassword')"
        :class="buttonClass"
        :on-click="
          () => {
            isOpen = !isOpen
          }
        "
      />
    </template>
    <template #default>
      <v-row dense>
        <v-col cols="12">
          <ValidationObserver ref="form" tag="form" slim @submit.prevent="submit">
            <validation-provider
              v-slot="{ errors }"
              :name="$t('profile.changePassword.form.current-password.name')"
              :rules="'required'"
              slim
            >
              <app-input
                v-model="password"
                :label="$t('profile.changePassword.form.current-password.label')"
                :input-type="isPasswordVisible ? 'text' : 'password'"
                :append-icon="isPasswordVisible ? 'visibility' : 'visibility_off'"
                :error-messages="errors"
                @appendIconClicked="togglePasswordVisibility"
              />
            </validation-provider>
          </ValidationObserver>
        </v-col>
      </v-row>
    </template>
    <template #sendingButton>
      <v-row dense>
        <v-col cols="12" class="d-flex justify-end">
          <app-button :button-title="$t('button.set')" :on-click="submit" />
        </v-col>
      </v-row>
    </template>
  </modal-template>
</template>

<script>
import ModalTemplate from '@/components/commons/modal/ModalTemplate'
import AppButton from '@/components/commons/button/AppButton'
import { mapActions, mapGetters } from 'vuex'
import { APP_STORE } from '@/store/modules/appStore/types'
import { AUTH } from '@/store/modules/auth/types'
import AppInput from '@/components/commons/input/AppInput'
export default {
  name: 'AppItemPasswordModal',
  components: { AppInput, AppButton, ModalTemplate },
  props: {
    input: {
      type: Object,
      required: true,
    },
    appKey: {
      type: String,
      required: true,
    },
    fieldKey: {
      type: String,
      required: true,
    },
    formKey: {
      type: String,
      required: true,
    },
    buttonClass: {
      type: String,
      default: () => '',
    },
  },
  data() {
    return {
      isOpen: false,
      password: '',
      isPasswordVisible: false,
    }
  },
  computed: {
    ...mapGetters(AUTH.NAMESPACE, { userId: AUTH.GETTERS.GET_LOGGED_USER_ID }),
  },
  methods: {
    ...mapActions(APP_STORE.NAMESPACE, [APP_STORE.ACTIONS.APP_SET_PASSWORD]),
    togglePasswordVisibility() {
      this.isPasswordVisible = !this.isPasswordVisible
    },
    async submit() {
      const isValid = await this.$refs.form.validate()
      if (!isValid) {
        return
      }
      await this[APP_STORE.ACTIONS.APP_SET_PASSWORD]({
        key: this.appKey,
        userId: this.userId,
        data: { password: this.password, formKey: this.formKey, fieldKey: this.fieldKey },
      }).then((res) => {
        if (res) {
          this.isOpen = false
          this.password = ''
          this.$refs.form.reset()
        }
      })
    },
  },
}
</script>

<style scoped></style>
