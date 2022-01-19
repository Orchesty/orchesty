<template>
  <modal-template v-model="isOpen" :title="input.value ? 'Change password' : 'Set password'">
    <template #button>
      <app-button
        :button-title="input.value ? 'Change password' : 'Set password'"
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
                dense
                :label="$t('profile.changePassword.form.current-password.label')"
                type="password"
                outlined
                :error-messages="errors"
              />
            </validation-provider>
          </ValidationObserver>
        </v-col>
      </v-row>
    </template>
    <template #sendingButton>
      <v-row dense>
        <v-col cols="12" class="d-flex justify-end">
          <app-button :button-title="$t('button.create')" :on-click="() => submit()" :flat="false" />
        </v-col>
      </v-row>
    </template>
  </modal-template>
</template>

<script>
import ModalTemplate from '@/components/commons/modal/ModalTemplate'
import AppButton from '@/components/commons/button/AppButton'
import { mapActions, mapState } from 'vuex'
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
  },
  data() {
    return {
      isOpen: false,
      password: '',
    }
  },
  computed: {
    ...mapState(AUTH.NAMESPACE, ['user']),
  },
  methods: {
    ...mapActions(APP_STORE.NAMESPACE, [APP_STORE.ACTIONS.APP_SET_PASSWORD]),
    async submit() {
      const isValid = await this.$refs.form.validate()
      if (!isValid) {
        return
      }
      await this[APP_STORE.ACTIONS.APP_SET_PASSWORD]({
        key: this.appKey,
        userId: this.user.user.id,
        data: { password: this.password },
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
