<template>
  <base-modal
    v-model="isOpen"
    :title="
      input.value ? $t('profile.changePassword') : $t('profile.setPassword')
    "
  >
    <template #activator="{ attrs, on }">
      <base-button
        color="secondary"
        :attrs="attrs"
        :on="on"
        :button-title="$t('button.setPassword')"
        outlined
        :disabled="disabled"
        :class="buttonClass"
      />
      <p class="mb-1">{{ label }}</p>
    </template>
    <template #content>
      <validation-provider
        v-slot="{ errors }"
        slim
        :name="name"
        rules="required"
      >
        <base-input
          v-model="password"
          :error-messages="errors"
          :label="label"
          :input-type="isPasswordVisible ? 'text' : 'password'"
          :append-icon="isPasswordVisible ? 'mdi-eye' : 'mdi-eye-off'"
          @appendIconClicked="togglePasswordVisibility"
        />
      </validation-provider>
    </template>
    <template #actions>
      <base-button
        :button-title="$t('button.set')"
        :loading="isSaving"
        :disabled="isSaving"
        :on-click="submit"
      />
    </template>
  </base-modal>
</template>

<script>
import BaseModal from "@/components/commons/BaseModal"
import BaseButton from "@/components/commons/BaseButton"
import BaseInput from "@/components/commons/BaseInput"
import { API } from "@/api"
import { callApi } from "@/utils/apiFetch"
import { APP_STORE } from "@/store/appStore/types"
import { mapGetters } from "vuex"
export default {
  name: "AppItemPasswordModal",
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
    label: {
      type: String,
      required: true,
    },
    name: {
      type: String,
      default: () => "",
    },
    formKey: {
      type: String,
      required: true,
    },
    disabled: {
      type: Boolean,
      default: false,
    },
    buttonClass: {
      type: String,
      default: () => "",
    },
  },
  data() {
    return {
      isOpen: false,
      isSaving: false,
      password: "",
      isPasswordVisible: false,
    }
  },
  computed: {
    ...mapGetters(APP_STORE.NAMESPACE, {
      sdk: APP_STORE.GETTERS.GET_SDK,
    }),
  },
  methods: {
    async submit() {
      this.isSaving = true
      await callApi({
        requestData: API.appStore.setPasswordApp,
        params: {
          key: this.appKey,
          sdk: this.sdk,
          data: {
            password: this.password,
            formKey: this.formKey,
            fieldKey: this.fieldKey,
          },
        },
      })
      this.isSaving = false
      this.isOpen = false
    },
    togglePasswordVisibility() {
      this.isPasswordVisible = !this.isPasswordVisible
    },
  },
}
</script>

<style scoped></style>
