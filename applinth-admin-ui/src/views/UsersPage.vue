<template>
  <AppLayout>
    <TableHeader :table-options="tableOptions" title="Uživatelé">
      <template #actions>
        <Button @click="addItem" color="secondary" outlined>Přidat</Button>
      </template>
    </TableHeader>
    <div class="table-medium">
      <Table :table-options="tableOptions">
        <template #actions="{ item }">
          <ActionsWrapper>
            <RoundButton @click="() => updateItem(item)" icon="pencil" />
            <RoundButton
              @click="() => deleteItem(item)"
              icon="delete"
              :disabled="!(admin && admin.adminId !== item.id)"
            />
          </ActionsWrapper>
        </template>
      </Table>
      <UserDeleteModal />
      <UserCreateModal />
    </div>
  </AppLayout>
</template>

<script lang="ts">
import { Component, Vue } from "vue-property-decorator";
import AppLayout from "../components/commons/layouts/AppLayout.vue";
import Table from "../components/commons/tables/Table.vue";
import TableHeader from "../components/commons/tables/TableHeader.vue";
import ActionsWrapper from "../components/commons/tables/ActionsWrapper.vue";
import Button from "../components/commons/inputsAndControls/Button.vue";
import RoundButton from "../components/commons/inputsAndControls/RoundButton.vue";
import { TableOptions } from "../types";
import { Admin, AdminsFilterEnum } from "../types/gqlGeneratedPrivate";
import { TablesNamespaces } from "../store/modules/tables";
import { EventBus } from "../enums/EventBus";
import { eventBus } from "../utils/eventBus";
import { Routes } from "../enums/Routes";
import { Getter } from "vuex-class";
import { authNamespace, AuthGetters } from "../store/modules/auth";
import UserDeleteModal from "@/components/app/admins/UserDeleteModal.vue";
import UserCreateModal from "@/components/app/admins/UserCreateModal.vue";

@Component({
  components: {
    UserCreateModal,
    UserDeleteModal,
    AppLayout,
    Button,
    RoundButton,
    Table,
    TableHeader,
    ActionsWrapper,
  },
})
export default class UsersPage extends Vue {
  @Getter(`${authNamespace}/${AuthGetters.GetAdministrator}`)
  admin!: Admin;

  tableOptions: TableOptions<Admin, AdminsFilterEnum> = {
    defaultSortBy: ["surname"],
    headers: [
      {
        text: "Jméno",
        sortable: true,
        align: "start",
        value: "firstname",
      },
      {
        text: "Příjmení",
        sortable: true,
        align: "start",
        value: "surname",
      },
      { text: "Email", sortable: true, align: "start", value: "username" },
      {
        text: "Superadmin",
        sortable: true,
        align: "start",
        value: "isSuperAdmin",
      },
      {
        text: "",
        sortable: false,
        value: "actions",
      },
    ],
    namespace: TablesNamespaces.UsersTable,
  };

  deleteItem(admin: Admin): void {
    eventBus.$emit(EventBus.UserDeleteModal, admin);
  }

  addItem(): void {
    eventBus.$emit(EventBus.UserCreateModal);
  }

  updateItem(admin: Admin): void {
    this.$router.push({
      name: Routes.UserUpdate,
      params: { id: admin.id.toString() },
    });
  }
}
</script>

<style lang="scss" scoped></style>
