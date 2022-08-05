<template>
  <AppLayout>
    <v-container fluid>
      <v-row dense>
        <v-col>
          <h1 class="mb-8">
            <router-link
              :to="{ name: Routes.Users }"
              title="Zpět na přehled uživatelů"
              class="text-decoration-none"
            >
              <v-icon class="router-icon mr-4 color-primary-blue">
                mdi-arrow-left-circle-outline
              </v-icon>
            </router-link>
            Uživatel {{ formData.firstname }} {{ formData.surname }}
          </h1>
        </v-col>
      </v-row>
      <v-row dense>
        <v-col>
          <ValidationObserver slim ref="form">
            <v-form class="form" @submit.prevent="onSubmit">
              <input type="submit" hidden />
              <TextField
                label="Jméno"
                v-model="formData.firstname"
                name="firstname"
                rules="required"
                autofocus
              />
              <TextField
                label="Příjmení"
                v-model="formData.surname"
                name="surname"
                rules="required"
              />
              <TextField
                label="Email"
                v-model="formData.username"
                name="username"
                rules="required|email"
              />
              <v-checkbox
                class="mt-0 mb-2"
                v-model="formData.isSuperAdmin"
                label="Superadmin"
              />
              <Button type="submit" :loading="isSending" color="secondary">
                Uložit
              </Button>
            </v-form>
          </ValidationObserver>
        </v-col>
      </v-row>
    </v-container>
  </AppLayout>
</template>

<script lang="ts">
import { Component, Vue } from "vue-property-decorator";
import { ValidationObserver } from "vee-validate";
import AppLayout from "../components/commons/layouts/AppLayout.vue";
import Button from "../components/commons/inputsAndControls/Button.vue";
import TextField from "../components/commons/inputsAndControls/TextField.vue";
import { Action } from "vuex-class";
import { api } from "../api";
import { TablesActions, TablesNamespaces } from "../store/modules/tables";
import { TableRefreshPayload } from "../types";
import {
  UpdateAdminInput,
  AdminQuery,
  AdminQueryVariables,
  UpdateAdminMutation,
  UpdateAdminMutationVariables,
} from "../types/gqlGeneratedPrivate";
import { apiClient, alerts } from "../utils";
import { Routes } from "../enums";

const emptyFormData: UpdateAdminInput = {
  username: "",
  firstname: "",
  surname: "",
  isSuperAdmin: false,
};

@Component({
  components: {
    AppLayout,
    Button,
    TextField,
    ValidationObserver,
  },
})
export default class UserUpdatePage extends Vue {
  Routes = Routes;

  isSending = false;

  adminId = 0;

  formData: UpdateAdminInput = {
    ...emptyFormData,
  };

  @Action(TablesActions.Refresh, {
    namespace: TablesNamespaces.AdminsTable,
  })
  refreshTable!: (payload: TableRefreshPayload) => Promise<void>;

  mounted() {
    const id = parseInt(this.$route.params.id);
    this.adminId = id;
    this.initialData(id);
  }

  async initialData(id: number): Promise<void> {
    this.isSending = true;
    const result = await apiClient.callGraphqlPrivate<
      AdminQuery,
      AdminQueryVariables
    >({
      ...api.admins.admin,
      variables: { id },
    });
    if (result.data) {
      this.formData = {
        username: result.data.admin.username,
        firstname: result.data.admin.firstname,
        surname: result.data.admin.surname,
        isSuperAdmin: result.data.admin.isSuperAdmin,
      };
    }
    this.isSending = false;
  }

  async onSubmit(): Promise<void> {
    const valid = await (this.$refs.form as any).validate();
    if (valid) {
      this.sendForm(this.formData);
    }
  }

  async sendForm(formData: UpdateAdminInput): Promise<void> {
    this.isSending = true;
    const result = await apiClient.callGraphqlPrivate<
      UpdateAdminMutation,
      UpdateAdminMutationVariables
    >({
      ...api.admins.updateAdmin,
      variables: {
        id: this.adminId,
        input: formData,
      },
    });
    if (result.data) {
      alerts.addSuccessAlert("UPDATE_ADMIN", "Uloženo");
      this.$router.push({
        name: Routes.Users,
      });
    }
    this.isSending = false;
  }
}
</script>

<style lang="scss" scoped>
.form {
  max-width: 30ch;
}
</style>
