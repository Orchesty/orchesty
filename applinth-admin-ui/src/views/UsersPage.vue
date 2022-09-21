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
      <template #actions="{ item }">
        <RoundButton @click="() => updateItem(item)" icon="pencil" />
        <RoundButton
          @click="() => deleteItem(item)"
          icon="delete"
          :disabled="!(currentUser && currentUser.id !== item.id)"
        />
      </template>
    </SimpleTable>
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
import { UsersListRequest } from "@/api/generated";
import Heading from "@/components/commons/typography/Heading.vue";

@Component({
  components: {
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

  users: User[] = [];

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
    this.isLoading = true;
    this.users = await callApi<UsersListRequest>(api.users.list);
    this.isLoading = false;
  }
}
</script>

<style lang="scss" scoped></style>
