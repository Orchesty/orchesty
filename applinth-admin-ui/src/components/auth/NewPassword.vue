<template>
  <ValidationObserver
    tag="form"
    @submit.prevent="submit"
    @keydown.enter="submit"
  >
    <TextField
      :name="$t('formLabels.newPassword')"
      rules="required"
      :label="$t('formLabels.passwordCheck')"
      v-model="newPasswordOne"
      type="password"
      autofocus
    />
    <TextField
      :name="$t('formLabels.newPassword')"
      :label="$t('formLabels.passwordCheck')"
      :rules="`confirmed:${$t('formLabels.newPassword')}`"
      v-model="newPasswordTwo"
      type="password"
    />
    <Button type="submit" :on-click="submit">
      {{ $t("button.change") }}
    </Button>
  </ValidationObserver>
</template>

<script lang="ts">
import { Component, Vue } from "vue-property-decorator";
import { ValidationObserver } from "vee-validate";
import TextField from "../commons/inputsAndControls/TextField.vue";
import Button from "../commons/inputsAndControls/Button.vue";

@Component({
  components: {
    Button,
    TextField,
    ValidationObserver,
  },
})
export default class NewPassword extends Vue {
  newPasswordOne = "";
  newPasswordTwo = "";

  async submit(): Promise<void> {
    // TODO implement using Firebase
    // const { data } = await apiClient.callGraphqlPublic<
    //   SetPasswordMutation,
    //   SetPasswordMutationVariables
    // >({
    //   ...api.auth.setPassword,
    //   variables: {
    //     input: {
    //       password: this.newPasswordOne,
    //       token: this.$route.params?.token,
    //     },
    //   },
    // });
    // if (data?.setPassword) {
    //   this.$router.push({ name: Routes.ChangedPassword });
    // }
  }
}
</script>

<style lang="scss" scoped></style>
