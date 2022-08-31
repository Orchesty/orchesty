<template>
  <AppLayout>
    <h1 class="mb-4">Uživatelé</h1>
    <Button class="mb-4" @click="addItem" color="secondary" outlined
      >Přidat</Button
    >
    <SimpleTable class="table-medium" :headers="headers" :items="users">
      <template #actions="{ item }">
        <RoundButton @click="() => updateItem(item)" icon="pencil" />
        <RoundButton
          @click="() => deleteItem(item)"
          icon="delete"
          :disabled="!(currentUser && currentUser.id !== item.id)"
        />
      </template>
    </SimpleTable>
    <UserDeleteModal />
    <UserCreateModal />
  </AppLayout>
</template>

<script lang="ts">
import AppLayout from "../components/commons/layouts/AppLayout.vue";
import Button from "../components/commons/inputsAndControls/Button.vue";
import RoundButton from "../components/commons/inputsAndControls/RoundButton.vue";
import SimpleTable from "@/components/commons/tables/SimpleTable.vue";
import UserCreateModal from "@/components/app/admins/UserCreateModal.vue";
import UserDeleteModal from "@/components/app/admins/UserDeleteModal.vue";
import { Component, Vue } from "vue-property-decorator";
import { EventBus } from "../enums/EventBus";
import { Getter } from "vuex-class";
import { Routes } from "../enums/Routes";
import { api } from "@/api";
import { authNamespace, AuthGetters, User } from "../store/modules/auth";
import { callApi } from "@/utils/apiClient";
import { eventBus } from "../utils/eventBus";
import {UsageStatsAppsRequest, UsersListRequest} from "@/api/generated";

@Component({
  components: {
    AppLayout,
    Button,
    RoundButton,
    SimpleTable,
    UserCreateModal,
    UserDeleteModal,
  },
})
export default class UsersPage extends Vue {
  @Getter(`${authNamespace}/${AuthGetters.GetUser}`)
  currentUser!: User;

  users: User[] = [];

  headers = [
    {
      text: "Email",
      sortable: true,
      align: "start",
      value: "email",
    },
    {
      text: "Name",
      sortable: true,
      align: "start",
      value: "displayName",
    },
    {
      text: "",
      sortable: false,
      value: "actions",
    },
  ];

  deleteItem(user: User): void {
    eventBus.$emit(EventBus.UserDeleteModal, user);
  }

  addItem(): void {
    eventBus.$emit(EventBus.UserCreateModal);
  }

  updateItem(user: User): void {
    this.$router.push({
      name: Routes.UserUpdate,
      params: { id: user.id },
    });
  }

  async created() {
    this.users = await callApi<UsersListRequest>(api.users.list, {
      tenantId: this.currentUser.tenantId ?? undefined,
    });
  }
}
</script>

<style lang="scss" scoped></style>
