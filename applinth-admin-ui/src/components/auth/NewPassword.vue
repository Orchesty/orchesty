<template>
  <ValidationObserver v-slot="{ handleSubmit }">
    <v-form class="mb-8" @submit.prevent="handleSubmit(submit)">
      <TextField
        vid="nove-heslo-1"
        name="nove-heslo-1"
        rules="required"
        label="Nové heslo"
        v-model="newPasswordOne"
        type="password"
        autocomplete="new-password"
        autofocus
      />
      <TextField
        name="nove-heslo-2"
        label="Nové heslo znova"
        rules="confirmed:nove-heslo-1"
        v-model="newPasswordTwo"
        type="password"
        autocomplete="new-password"
        class="d-block mb-4"
      />
      <Button
        type="submit"
        color="primary"
        no-text-transform
        large
        block
        class="button"
      >
        Změnit
      </Button>
    </v-form>
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

<style lang="scss" scoped>
.button {
  font-size: 20px;
  padding: 10px;
}
</style>
