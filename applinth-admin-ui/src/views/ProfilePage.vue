<template>
  <AppLayout>
    <Heading class="mb-5">
      {{ $t("profilePage.header.profile") }} {{ currentUser.email }}
    </Heading>
    <ValidationObserver v-slot="{ invalid, validate }">
      <v-form class="form" @submit.prevent="validate().then(submitFormName)">
        <TextField
          :name="$t('formLabels.userName')"
          rules="required"
          :label="$t('formLabels.userName')"
          v-model="formLoggedAdmin.displayName"
        />
        <Button
          type="submit"
          :disabled="invalid"
          :loading="isSendingUpdateUser"
          >{{ $t("button.save") }}</Button
        >
      </v-form>
    </ValidationObserver>

    <v-divider class="form my-6" />

    <Heading class="mb-2">{{ $t("profilePage.header.password") }}</Heading>
    <ValidationObserver
      v-slot="{ invalid, validate }"
      ref="observerNewPassword"
    >
      <v-form
        class="form"
        @submit.prevent="validate().then(submitFormNewPassword)"
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
        <Button
          type="submit"
          :disabled="invalid"
          :loading="isSendingNewPassword"
          >{{ $t("button.save") }}</Button
        >
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
import { alerts, i18n } from "../utils";
import {
  AuthActions,
  AuthGetters,
  authNamespace,
  User,
} from "../store/modules/auth";
import { Action, Getter } from "vuex-class";
import Heading from "@/components/commons/typography/Heading.vue";
import { ChangePassword, UpdateUserInfo } from "@/types/CurrentUser";

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
  currentUser!: User;

  @Action(`${authNamespace}/${AuthActions.ChangePassword}`)
  private firebaseChangePassword!: (payload: string) => Promise<boolean>;

  @Action(`${authNamespace}/${AuthActions.Reauthenticate}`)
  private reauthenticate!: (payload: string) => Promise<boolean>;

  @Action(`${authNamespace}/${AuthActions.UpdateSettings}`)
  private updateUserInfo!: (payload: UpdateUserInfo) => Promise<boolean>;

  isSendingUpdateUser = false;
  formLoggedAdmin: UpdateUserInfo = {
    displayName: "",
  };

  isFormNewPasswordValid = true;
  isSendingNewPassword = false;
  formNewPassword: ChangePassword = {
    oldPassword: "",
    newPasswordOne: "",
    newPasswordTwo: "",
  };

  mounted() {
    this.formLoggedAdmin = {
      displayName: this.currentUser.name || "",
    };
  }

  async submitFormName() {
    this.isSendingUpdateUser = true;
    const result = await this.updateUserInfo(this.formLoggedAdmin);
    if (result) {
      alerts.addSuccessAlert(
        "UPDATE_LOGGED_ADMIN",
        i18n.t("message.saved") as string
      );
    }
    this.isSendingUpdateUser = false;
  }

  async submitFormNewPassword() {
    this.isFormNewPasswordValid = true;
    this.isSendingNewPassword = true;
    const result = await this.updateLoggedAdminPassword(this.formNewPassword);

    if (result) {
      alerts.addSuccessAlert(
        "UPDATE_LOGGED_ADMIN_PASSWORD",
        i18n.t("message.saved") as string
      );
      (this.$refs.formNewPassword as HTMLFormElement).reset();
      this.$nextTick(() => {
        (this.$refs.observerNewPassword as any).reset();
      });
    }
    this.isSendingNewPassword = false;
  }

  async updateLoggedAdminPassword(input: ChangePassword): Promise<boolean> {
    if (input.newPasswordOne !== input.newPasswordTwo) {
      this.isFormNewPasswordValid = false;
      return false;
    }

    const currentPasswordIsValid = await this.reauthenticate(input.oldPassword);
    if (currentPasswordIsValid) {
      return await this.firebaseChangePassword(input.newPasswordOne);
    }

    return false;
  }
}
</script>

<style lang="scss" scoped>
.form {
  max-width: 45ch;
}
</style>
