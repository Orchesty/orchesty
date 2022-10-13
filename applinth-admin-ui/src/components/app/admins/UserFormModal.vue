<template>
  <Modal
    :title="title"
    :cancel-btn-text="$t('button.cancel')"
    :is-sending="isSending"
    v-model="isOpen"
    :on-cancel="closeModal"
    :persistent="isEdit"
  >
    <template>
      <ValidationObserver slim ref="form">
        <v-form class="form" @submit.prevent="onSubmit">
          <input type="submit" hidden />
          <TextField
            v-if="!isEdit"
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
import { Component, Vue, Watch } from "vue-property-decorator";
import Modal from "../../commons/layouts/Modal.vue";
import { ValidationObserver } from "vee-validate";
import Button from "../../commons/inputsAndControls/Button.vue";
import TextField from "../../commons/inputsAndControls/TextField.vue";
import { EventBus, Routes } from "@/enums";
import { eventBus } from "@/utils/eventBus";
import { api } from "@/api";
import {
  CreateUser,
  UpdateUser,
  UsersCreateOperationRequest,
  UsersGetRequest,
  UsersUpdateOperationRequest,
} from "@/api/generated";
import { alerts, callApi } from "@/utils";
import { Route } from "vue-router";

const emptyFormData: CreateUser | UpdateUser = {
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
export default class UserFormModal extends Vue {
  isOpen = false;
  isSending = false;
  isEdit = false;
  email = "";

  formData: CreateUser | UpdateUser = {
    ...emptyFormData,
  };

  created(): void {
    eventBus.$on(EventBus.UserCreateModal, () => {
      this.initForm();
      this.isOpen = true;
    });

    if (this.$route.name === Routes.UserUpdate) {
      this.initForm();
      this.isOpen = this.isEdit = true;
      this.fetchUserDetail();
    }
  }

  async fetchUserDetail() {
    this.isSending = true;
    if (this.$route.params.id) {
      const userResponse = await callApi<UsersGetRequest>(api.users.get, {
        uid: this.$route.params.id,
      });

      if (userResponse?.user) {
        this.email = userResponse.user.email;
        this.formData = {
          displayName: userResponse.user.displayName,
        };
      }
    } else {
      this.closeModal();
    }

    this.isSending = false;
  }

  async onSubmit(): Promise<void> {
    const valid = await (this.$refs.form as any).validate();
    if (valid) {
      this.sendForm(this.formData);
    }
  }

  async sendForm(formData: CreateUser | UpdateUser): Promise<void> {
    this.isSending = true;

    let response;

    if (this.isEdit) {
      response = await callApi<UsersUpdateOperationRequest>(api.users.update, {
        uid: this.$route.params.id,
        usersUpdateRequest: formData,
      });
    } else {
      response = await callApi<UsersCreateOperationRequest>(api.users.create, {
        usersCreateRequest: formData as CreateUser,
      });
    }

    if (response?.user) {
      alerts.addSuccessAlert(
        this.isEdit ? "UPDATE_ADMIN" : "CREATE_ADMIN",
        this.isEdit ? "message.saved" : "message.userCreated"
      );

      eventBus.$emit(EventBus.UsersRefreshList);

      this.closeModal();
    }

    this.isSending = false;
  }

  get title() {
    return this.isEdit
      ? `${this.$t("usersPage.updateUser")} ${this.email}`
      : this.$t("usersPage.newUser");
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

  closeModal() {
    this.initForm();

    if (this.isEdit) {
      this.$router.push({
        name: Routes.Users,
      });
    }

    this.isOpen = this.isEdit = false;
  }

  @Watch("$route")
  onChangeRoute(route: Route) {
    if (route.name === Routes.UserUpdate) {
      this.initForm();
      this.isOpen = this.isEdit = true;
      this.fetchUserDetail();
    }
  }
}
</script>
