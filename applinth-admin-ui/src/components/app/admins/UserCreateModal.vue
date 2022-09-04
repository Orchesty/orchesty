<template>
  <Modal
    title="Nový uživatel"
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
            label="Email"
            v-model="formData.username"
            name="username"
            rules="required|email"
            autofocus
          />
        </v-form>
      </ValidationObserver>
    </template>
    <template slot="actions-right">
      <Button :loading="isSending" color="secondary" @click="onSubmit">
        Uložit
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
import { EventBus } from "../../../enums";
import { eventBus } from "../../../utils/eventBus";
import { CreateAdminInput } from "../../../types/gqlGeneratedPrivate";
import { Action } from "vuex-class";
import { TablesActions, TablesNamespaces } from "../../../store/modules/tables";
import { TableRefreshPayload } from "../../../types";

const emptyFormData: CreateAdminInput = {
  username: "",
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
  isOpen = false;
  isSending = false;

  formData: CreateAdminInput = {
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

  async sendForm(formData: CreateAdminInput): Promise<void> {
    this.isSending = true;
    // TODO call backend API
    // const result = await apiClient.callGraphqlPrivate<
    //   MutationUpdateAdminArgs,
    //   MutationCreateAdminArgs
    // >({
    //   ...api.users.createUser,
    //   variables: {
    //     input: formData,
    //   },
    // });
    // if (result.data) {
    //   alerts.addSuccessAlert("CREATE_ADMIN", "Uloženo");
    //   this.refreshTable({
    //     namespace: TablesNamespaces.UsersTable,
    //   });
    // }
    this.initForm();
    this.isOpen = false;
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
