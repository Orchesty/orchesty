<template>
  <AppLayout>
    <Heading class="mb-4">{{ $t("usersPage.header") }}</Heading>
    <Button class="mb-4" @click="addItem">{{ $t("button.add") }}</Button>
    <SimpleTable
      :loading="isLoading"
      class="table-medium"
      :headers="headers"
      :items="users"
    >
      <template #disabled="{ item }">
        <span v-if="item.disabled">{{ $t("button.yes") }}</span>
        <span v-else>{{ $t("button.no") }}</span>
      </template>
      <template #actions="{ item }">
        <RoundButton @click="() => updateItem(item)" icon="pencil" />
        <RoundButton
          @click="() => deleteItem(item)"
          icon="delete"
          :disabled="!(currentUser && currentUser.id !== item.uid)"
        />
      </template>
    </SimpleTable>

    <user-create-modal />
    <user-delete-modal />
  </AppLayout>
</template>

<script lang="ts">
import AppLayout from "../components/commons/layouts/AppLayout.vue";
import Button from "../components/commons/inputsAndControls/Button.vue";
import RoundButton from "../components/commons/inputsAndControls/RoundButton.vue";
import SimpleTable from "@/components/commons/tables/SimpleTable.vue";
import { Component, Vue } from "vue-property-decorator";
import { EventBus } from "../enums/EventBus";
import { Getter } from "vuex-class";
import { Routes } from "../enums/Routes";
import { api } from "@/api";
import { authNamespace, AuthGetters, User } from "../store/modules/auth";
import { callApi } from "@/utils/apiClient";
import { eventBus } from "../utils/eventBus";
import { OutputUser, UsersListRequest } from "@/api/generated";
import Heading from "@/components/commons/typography/Heading.vue";
import UserCreateModal from "@/components/app/admins/UserCreateModal.vue";
import UserDeleteModal from "@/components/app/admins/UserDeleteModal.vue";

@Component({
  components: {
    UserDeleteModal,
    UserCreateModal,
    Heading,
    AppLayout,
    Button,
    RoundButton,
    SimpleTable,
  },
})
export default class UsersPage extends Vue {
  @Getter(`${authNamespace}/${AuthGetters.GetUser}`)
  currentUser!: User;

  isLoading = false;

  users: OutputUser[] = [];

  headers = [
    {
      text: "grids.headers.email",
      sortable: true,
      align: "start",
      value: "email",
    },
    {
      text: "grids.headers.name",
      sortable: true,
      align: "start",
      value: "displayName",
    },
    {
      text: "grids.headers.disabled",
      sortable: false,
      align: "start",
      value: "disabled",
    },
    {
      text: "",
      sortable: false,
      value: "actions",
    },
  ];

  deleteItem(user: OutputUser): void {
    eventBus.$emit(EventBus.UserDeleteModal, user);
  }

  addItem(): void {
    eventBus.$emit(EventBus.UserCreateModal);
  }

  updateItem(user: OutputUser): void {
    this.$router.push({
      name: Routes.UserUpdate,
      params: { id: user.uid as string },
    });
  }
  created() {
    eventBus.$on(EventBus.UsersRefreshList, this.fetchUsers);

    this.fetchUsers();
  }

  private async fetchUsers(): Promise<void> {
    this.isLoading = true;
    this.users = await callApi<UsersListRequest>(api.users.list);
    this.isLoading = false;
  }
}
</script>

<style lang="scss" scoped></style>
