<template>
  <Modal
    :title="$t('usersPage.newUser')"
    :cancel-btn-text="$t('button.cancel')"
    :is-sending="isSending"
    v-model="isOpen"
    :on-cancel="cancel"
  >
    <template>
      <ValidationObserver slim ref="form">
        <v-form class="form" @submit.prevent="onSubmit">
          <input type="submit" hidden />
          <TextField
            :label="$t('formLabels.email')"
            v-model="formData.email"
            name="email"
            rules="required|email"
            autofocus
          />
          <TextField
            :label="$t('formLabels.userName')"
            v-model="formData.displayName"
            name="displayName"
            rules="required"
          />
        </v-form>
      </ValidationObserver>
    </template>
    <template slot="actions-right">
      <Button :loading="isSending" color="secondary" @click="onSubmit">
        {{ $t("button.save") }}
      </Button>
    </template>
  </Modal>
</template>

<script lang="ts">
import { Component, Vue } from "vue-property-decorator";
import Modal from "../../commons/layouts/Modal.vue";
import { ValidationObserver } from "vee-validate";
import Button from "../../commons/inputsAndControls/Button.vue";
import TextField from "../../commons/inputsAndControls/TextField.vue";
import { EventBus } from "@/enums";
import { eventBus } from "@/utils/eventBus";
import { Action, Getter } from "vuex-class";
import { TablesActions, TablesNamespaces } from "../../../store/modules/tables";
import { TableRefreshPayload } from "../../../types";
import { AuthGetters, authNamespace, User } from "@/store/modules/auth";
import { api } from "@/api";
import { CreateUser, UsersCreateOperationRequest } from "@/api/generated";
import { alerts, callApi } from "@/utils";

const emptyFormData: CreateUser = {
  email: "",
  disabled: false,
  displayName: "",
};

@Component({
  components: {
    Button,
    Modal,
    TextField,
    ValidationObserver,
  },
})
export default class UserCreateModal extends Vue {
  @Getter(`${authNamespace}/${AuthGetters.GetUser}`)
  currentUser!: User;

  isOpen = false;
  isSending = false;

  formData: CreateUser = {
    ...emptyFormData,
  };

  @Action(TablesActions.Refresh, {
    namespace: TablesNamespaces.UsersTable,
  })
  refreshTable!: (payload: TableRefreshPayload) => Promise<void>;

  created(): void {
    eventBus.$on(EventBus.UserCreateModal, () => {
      this.initForm();
      this.isOpen = true;
    });
  }

  async onSubmit(): Promise<void> {
    const valid = await (this.$refs.form as any).validate();
    if (valid) {
      this.sendForm(this.formData);
    }
  }

  async sendForm(formData: CreateUser): Promise<void> {
    this.isSending = true;

    const res = await callApi<UsersCreateOperationRequest>(api.users.create, {
      usersCreateRequest: formData,
    });

    if (res?.user) {
      alerts.addSuccessAlert(
        "CREATE_ADMIN",
        this.$t("message.userCreated") as string
      );

      eventBus.$emit(EventBus.UsersRefreshList);

      this.initForm();
      this.isOpen = false;
    }

    this.isSending = false;
  }

  initForm(): void {
    this.formData = {
      ...emptyFormData,
    };
    this.isSending = false;
    this.$nextTick(() => {
      if (this.$refs.form) {
        (this.$refs.form as any).reset();
      }
    });
  }

  cancel(): void {
    this.isOpen = false;
    this.initForm();
  }
}
</script>

<style lang="scss" scoped></style>
