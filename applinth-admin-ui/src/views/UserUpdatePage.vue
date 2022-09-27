<template>
  <AppLayout>
    <div class="d-flex align-center mb-5">
      <router-link :to="{ name: Routes.Users }" class="text-decoration-none">
        <v-icon class="router-icon mr-4 color-primary-blue">
          mdi-arrow-left-circle-outline
        </v-icon>
      </router-link>
      <Heading>
        {{ $t("userUpdatePage.header") }} {{ formData.displayName }}
      </Heading>
    </div>
    <ValidationObserver slim ref="form">
      <v-form class="form" @submit.prevent="onSubmit">
        <input type="submit" hidden />
        <TextField
          :label="$t('formLabels.userName')"
          v-model="formData.displayName"
          name="username"
          rules="required"
          autofocus
        />
        <v-checkbox
          class="mt-0 mb-2"
          v-model="formData.disabled"
          :label="$t('formLabels.disabled')"
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
import { Action, Getter } from "vuex-class";
import { TablesActions, TablesNamespaces } from "@/store/modules/tables";
import { TableRefreshPayload } from "../types";
import { Routes } from "@/enums";
import Heading from "@/components/commons/typography/Heading.vue";
import {
  UpdateUser,
  UsersGetRequest,
  UsersUpdateOperationRequest,
} from "@/api/generated";
import { api } from "@/api";
import { alerts, callApi } from "@/utils";
import { AuthGetters, authNamespace, User } from "@/store/modules/auth";

const emptyFormData: UpdateUser = {
  displayName: "",
  disabled: false,
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
  @Getter(`${authNamespace}/${AuthGetters.GetUser}`)
  currentUser!: User;

  Routes = Routes;

  isSending = false;

  adminId = "";

  formData: UpdateUser = {
    ...emptyFormData,
  };

  @Action(TablesActions.Refresh, {
    namespace: TablesNamespaces.UsersTable,
  })
  refreshTable!: (payload: TableRefreshPayload) => Promise<void>;

  mounted() {
    this.adminId = this.$route.params.id;
    this.initialData(this.adminId);
  }

  async initialData(id: string): Promise<void> {
    this.isSending = true;

    const res = await callApi<UsersGetRequest>(api.users.get, {
      uid: id,
    });

    if (res?.user) {
      this.formData = {
        displayName: res.user.displayName,
        disabled: res.user.disabled,
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

  async sendForm(formData: UpdateUser): Promise<void> {
    this.isSending = true;

    const res = await callApi<UsersUpdateOperationRequest>(api.users.update, {
      uid: this.adminId,
      usersUpdateRequest: formData,
    });

    if (res?.user) {
      alerts.addSuccessAlert(
        "UPDATE_ADMIN",
        this.$t("message.saved") as string
      );
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
