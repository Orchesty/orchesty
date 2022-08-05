<template>
  <AppLayout>
    <v-container fluid>
      <v-row dense>
        <v-col>
          <h1 class="mb-8">Účet</h1>
        </v-col>
      </v-row>
      <v-row dense>
        <v-col>
          <ValidationObserver v-slot="{ handleSubmit }">
            <v-form
              class="form mb-8 text-center"
              @submit.prevent="handleSubmit(submitFormName)"
            >
              <TextField
                name="firstname"
                label="Jméno"
                v-model="formLoggedAdmin.firstname"
              />
              <TextField
                name="surename"
                label="Příjmení"
                v-model="formLoggedAdmin.surname"
              />
              <TextField
                name="username"
                rules="required|email"
                label="Email"
                v-model="formLoggedAdmin.username"
              />
              <Button type="submit" color="secondary">Uložit</Button>
            </v-form>
          </ValidationObserver>
        </v-col>
      </v-row>
      <v-row dense>
        <v-col>
          <ValidationObserver
            v-slot="{ handleSubmit }"
            ref="observerNewPassword"
          >
            <v-form
              class="form text-center"
              v-model="isFormNewPasswordValid"
              @submit.prevent="handleSubmit(submitFormNewPassword)"
              ref="formNewPassword"
            >
              <TextField
                name="stare-heslo"
                label="Staré heslo"
                rules="required"
                v-model="formNewPassword.oldPassword"
                type="password"
                autocomplete="current-password"
              />
              <TextField
                vid="nove-heslo-1"
                name="nove-heslo-1"
                rules="required"
                label="Nové heslo"
                v-model="formNewPassword.newPasswordOne"
                type="password"
                autocomplete="new-password"
              />
              <TextField
                name="nove-heslo-2"
                label="Nové heslo znova"
                rules="confirmed:nove-heslo-1"
                v-model="formNewPassword.newPasswordTwo"
                type="password"
                autocomplete="new-password"
              />
              <Button type="submit" color="secondary">Uložit</Button>
            </v-form>
          </ValidationObserver>
        </v-col>
      </v-row>
    </v-container>
  </AppLayout>
</template>

<script lang="ts">
import AppLayout from "../components/commons/layouts/AppLayout.vue";
import Button from "../components/commons/inputsAndControls/Button.vue";
import Table from "../components/commons/tables/Table.vue";
import TextField from "../components/commons/inputsAndControls/TextField.vue";
import { Component, Vue } from "vue-property-decorator";
import { ValidationObserver } from "vee-validate";
import { alerts, apiClient } from "../utils";
import { api } from "../api";
import { AuthGetters, authNamespace } from "../store/modules/auth";
import { Getter } from "vuex-class";
import { Admin } from "../types/gqlGeneratedPublic";
import {
  UpdateLoggedAdminInput,
  UpdateLoggedAdminMutationVariables,
  UpdateLoggedAdminPasswordInput,
  UpdateLoggedAdminPasswordMutationVariables,
} from "../types/gqlGeneratedPrivate";

@Component({
  components: {
    AppLayout,
    Button,
    Table,
    TextField,
    ValidationObserver,
  },
})
export default class ProfilePage extends Vue {
  @Getter(`${authNamespace}/${AuthGetters.GetAdministrator}`)
  admin!: Admin;

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
    this.formLoggedAdmin = {
      firstname: this.admin.firstname,
      surname: this.admin.surname,
      username: this.admin.username,
    };
  }

  async submitFormName() {
    const result = await this.updateLoggedAdmin(this.formLoggedAdmin);
    if (result.data) {
      alerts.addSuccessAlert("UPDATE_LOGGED_ADMIN", "Uloženo");
    }
  }

  async updateLoggedAdmin(input: UpdateLoggedAdminInput) {
    return await apiClient.callGraphqlPrivate<
      Admin,
      UpdateLoggedAdminMutationVariables
    >({
      ...api.admins.updateLoggedAdmin,
      variables: { input },
    });
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
    return await apiClient.callGraphqlPrivate<
      boolean,
      UpdateLoggedAdminPasswordMutationVariables
    >({
      ...api.admins.updateLoggedAdminPassword,
      variables: { input },
    });
  }
}
</script>

<style lang="scss" scoped>
.form {
  max-width: 30ch;
}
</style>
