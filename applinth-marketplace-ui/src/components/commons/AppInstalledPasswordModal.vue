<template>
  <base-modal
    v-model="isOpen"
    :title="input.value ? 'Change password' : 'Set password'"
  >
    <template #activator="{ attrs, on }">
      <base-button
        color="secondary"
        :attrs="attrs"
        :on="on"
        :button-title="$t('button.password')"
        outlined
        :disabled="disabled"
      />
    </template>
    <template #content>
      <base-input
        v-model="password"
        :label="$t('profile.changePassword.form.current-password.label')"
        input-type="password"
        outlined
      />
    </template>
    <template #actions>
      <base-button :button-title="$t('button.update')" :on-click="submit" />
    </template>
  </base-modal>
</template>

<script>
import BaseModal from '@/components/commons/BaseModal'
import BaseButton from '@/components/commons/BaseButton'
import BaseInput from '@/components/commons/BaseInput'
import { API } from '@/api'
import { callApi } from '@/utils/apiFetch'
export default {
  name: 'AppItemPasswordModal',
  components: {
    BaseInput,
    BaseButton,
    BaseModal,
  },
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
    disabled: {
      type: Boolean,
      default: false,
    },
  },
  data() {
    return {
      isOpen: false,
      password: '',
    }
  },
  methods: {
    async submit() {
      await callApi({
        requestData: API.appStore.setPasswordApp,
        data: {
          key: this.appKey,
          data: {
            password: this.password,
            formKey: this.formKey,
            fieldKey: this.fieldKey,
          },
        },
      })
    },
  },
}
</script>

<style scoped></style>
