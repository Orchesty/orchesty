<template>
  <AppLayout>
    <Heading class="mb-5">{{ $t("profilePage.header.profile") }}</Heading>
    <ValidationObserver v-slot="{ handleSubmit }">
      <v-form class="form" @submit.prevent="handleSubmit(submitFormName)">
        <TextField
          :name="$t('formLabels.firstName')"
          :label="$t('formLabels.firstName')"
          v-model="formLoggedAdmin.firstname"
        />
        <TextField
          :name="$t('formLabels.surname')"
          :label="$t('formLabels.surname')"
          v-model="formLoggedAdmin.surname"
        />
        <TextField
          :name="$t('formLabels.userName')"
          rules="required|email"
          :label="$t('formLabels.userName')"
          v-model="formLoggedAdmin.username"
        />
        <Button type="submit">{{ $t("button.save") }}</Button>
      </v-form>
    </ValidationObserver>

    <v-divider class="form my-6" />

    <Heading class="mb-2">{{ $t("profilePage.header.password") }}</Heading>
    <ValidationObserver v-slot="{ handleSubmit }" ref="observerNewPassword">
      <v-form
        class="form"
        v-model="isFormNewPasswordValid"
        @submit.prevent="handleSubmit(submitFormNewPassword)"
        ref="formNewPassword"
      >
        <TextField
          :name="$t('formLabels.password')"
          :label="$t('formLabels.password')"
          rules="required"
          v-model="formNewPassword.oldPassword"
          type="password"
          :autocomplete="$t('formLabels.password')"
        />
        <TextField
          vid="newPassword"
          :name="$t('formLabels.newPassword')"
          rules="required"
          :label="$t('formLabels.newPassword')"
          v-model="formNewPassword.newPasswordOne"
          type="password"
          :autocomplete="$t('formLabels.newPassword')"
        />
        <TextField
          :name="$t('formLabels.passwordCheck')"
          :label="$t('formLabels.passwordCheck')"
          :rules="`confirmed:${$t('formLabels.newPassword')}`"
          v-model="formNewPassword.newPasswordTwo"
          type="password"
          :autocomplete="$t('formLabels.passwordCheck')"
        />
        <Button type="submit">{{ $t("button.save") }}</Button>
      </v-form>
    </ValidationObserver>
  </AppLayout>
</template>

<script lang="ts">
import AppLayout from "../components/commons/layouts/AppLayout.vue";
import Button from "../components/commons/inputsAndControls/Button.vue";
import Table from "../components/commons/tables/Table.vue";
import TextField from "../components/commons/inputsAndControls/TextField.vue";
import { Component, Vue } from "vue-property-decorator";
import { ValidationObserver } from "vee-validate";
import { alerts } from "../utils";
import { AuthGetters, authNamespace, User } from "../store/modules/auth";
import { Getter } from "vuex-class";
import {
  UpdateLoggedAdminInput,
  UpdateLoggedAdminPasswordInput,
} from "../types/gqlGeneratedPrivate";
import Heading from "@/components/commons/typography/Heading.vue";

@Component({
  components: {
    Heading,
    AppLayout,
    Button,
    Table,
    TextField,
    ValidationObserver,
  },
})
export default class ProfilePage extends Vue {
  @Getter(`${authNamespace}/${AuthGetters.GetUser}`)
  user!: User;

  isFormNameValid = false;
  formLoggedAdmin: UpdateLoggedAdminInput = {
    username: "",
    firstname: "",
    surname: "",
  };

  isFormNewPasswordValid = false;
  formNewPassword: UpdateLoggedAdminPasswordInput = {
    oldPassword: "",
    newPasswordOne: "",
    newPasswordTwo: "",
  };

  mounted() {
    // TODO redo the form
    // this.formLoggedAdmin = {
    // firstname: this.user.firstname,
    // surname: this.user.surname,
    // username: this.user.username,
    // };
  }

  async submitFormName() {
    const result = await this.updateLoggedAdmin(this.formLoggedAdmin);
    if (result.data) {
      alerts.addSuccessAlert("UPDATE_LOGGED_ADMIN", "Uloženo");
    }
  }

  async updateLoggedAdmin(input: UpdateLoggedAdminInput) {
    // TODO implement using Firebase
    // return await apiClient.callGraphqlPrivate<
    //   Admin,
    //   UpdateLoggedAdminMutationVariables
    // >({
    //   ...api.users.updateLoggedUser,
    //   variables: { input },
    // });
    return { data: {} };
  }

  async submitFormNewPassword() {
    const result = await this.updateLoggedAdminPassword(this.formNewPassword);
    if (result.data) {
      alerts.addSuccessAlert("UPDATE_LOGGED_ADMIN_PASSWORD", "Uloženo");
      (this.$refs.formNewPassword as HTMLFormElement).reset();
      this.$nextTick(() => {
        (this.$refs.observerNewPassword as any).reset();
      });
    }
  }

  async updateLoggedAdminPassword(input: UpdateLoggedAdminPasswordInput) {
    // TODO implement using Firebase
    // return await apiClient.callGraphqlPrivate<
    //   boolean,
    //   UpdateLoggedAdminPasswordMutationVariables
    // >({
    //   ...api.users.updateLoggedUserPassword,
    //   variables: { input },
    // });
    return { data: {} };
  }
}
</script>

<style lang="scss" scoped>
.form {
  max-width: 30ch;
}
</style>
