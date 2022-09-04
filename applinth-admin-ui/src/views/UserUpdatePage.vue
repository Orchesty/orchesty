<template>
  <AppLayout>
    <div class="d-flex align-center mb-4">
      <router-link :to="{ name: Routes.Users }">
        <v-icon class="router-icon mr-4 color-primary-blue">
          mdi-arrow-left-circle-outline
        </v-icon>
      </router-link>
      <Heading>
        {{ $t("userUpdatePage.header") }} {{ formData.firstname }}
        {{ formData.surname }}
      </Heading>
    </div>
    <ValidationObserver slim ref="form">
      <v-form class="form" @submit.prevent="onSubmit">
        <input type="submit" hidden />
        <TextField
          :label="$t('formLabels.firstName')"
          v-model="formData.firstname"
          :name="$t('formLabels.firstName')"
          rules="required"
          autofocus
        />
        <TextField
          :label="$t('formLabels.surname')"
          v-model="formData.surname"
          :name="$t('formLabels.surname')"
          rules="required"
        />
        <TextField
          :label="$t('formLabels.userName')"
          v-model="formData.username"
          :name="$t('formLabels.userName')"
          rules="required|email"
        />
        <v-checkbox
          class="mt-0 mb-2"
          v-model="formData.isSuperAdmin"
          :label="$t('formLabels.superAdmin')"
        />
        <Button type="submit" :loading="isSending">
          {{ $t("button.save") }}
        </Button>
      </v-form>
    </ValidationObserver>
  </AppLayout>
</template>

<script lang="ts">
import { Component, Vue } from "vue-property-decorator";
import { ValidationObserver } from "vee-validate";
import AppLayout from "../components/commons/layouts/AppLayout.vue";
import Button from "../components/commons/inputsAndControls/Button.vue";
import TextField from "../components/commons/inputsAndControls/TextField.vue";
import { Action } from "vuex-class";
import { TablesActions, TablesNamespaces } from "../store/modules/tables";
import { TableRefreshPayload } from "../types";
import { UpdateAdminInput } from "../types/gqlGeneratedPrivate";
import { Routes } from "../enums";
import Heading from "@/components/Heading.vue";

const emptyFormData: UpdateAdminInput = {
  username: "",
  firstname: "",
  surname: "",
  isSuperAdmin: false,
};

@Component({
  components: {
    Heading,
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
    namespace: TablesNamespaces.UsersTable,
  })
  refreshTable!: (payload: TableRefreshPayload) => Promise<void>;

  mounted() {
    const id = parseInt(this.$route.params.id);
    this.adminId = id;
    this.initialData(id);
  }

  async initialData(id: number): Promise<void> {
    this.isSending = true;
    // TODO implement using Firebase
    // const result = await apiClient.callGraphqlPrivate<
    //   AdminQuery,
    //   AdminQueryVariables
    // >({
    //   ...api.users.user,
    //   variables: { id },
    // });
    // if (result.data) {
    //   this.formData = {
    //     username: result.data.admin.username,
    //     firstname: result.data.admin.firstname,
    //     surname: result.data.admin.surname,
    //     isSuperAdmin: result.data.admin.isSuperAdmin,
    //   };
    // }
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
    // TODO implement using Firebase
    // const result = await apiClient.callGraphqlPrivate<
    //   UpdateAdminMutation,
    //   UpdateAdminMutationVariables
    // >({
    //   ...api.users.updateUser,
    //   variables: {
    //     id: this.adminId,
    //     input: formData,
    //   },
    // });
    // if (result.data) {
    //   alerts.addSuccessAlert("UPDATE_ADMIN", "Uloženo");
    //   this.$router.push({
    //     name: Routes.Users,
    //   });
    // }
    this.isSending = false;
  }
}
</script>

<style lang="scss" scoped>
.form {
  max-width: 30ch;
}
</style>
