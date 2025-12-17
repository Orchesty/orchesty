<template>
  <v-app>
    <global-notifications />

    <navigation />

    <v-main>
      <sidebar-toggle :title="$t('navigation.topologies')">
        <template #sidebar>
          <sidebar />
        </template>
        <template #default>
          <router-view />
        </template>
      </sidebar-toggle>
    </v-main>

    <!--    init emit listener modals-->
    <modal-run-topology />
    <modal-delete-folder />
    <modal-edit-folder />
    <modal-delete-topology />
    <modal-move-topology />
    <modal-edit-topology />
    <modal-create-folder />
    <modal-create-topology />
    <modal-import-topology />
    <!--    end-->
  </v-app>
</template>

<script>
import GlobalNotifications from "../../commons/notifications/GlobalNotifications.vue"
import Navigation from "../navigation/Navigation.vue"
import SidebarToggle from "@/components/layout/sidebar/SidebarToggle.vue"
import Sidebar from "@/components/layout/sidebar/Sidebar.vue"
import ModalRunTopology from "@/components/app/topology/modal/ModalRunTopology.vue"
import ModalDeleteFolder from "@/components/app/folder/modal/ModalDeleteFolder.vue"
import ModalEditFolder from "@/components/app/folder/modal/ModalEditFolder.vue"
import ModalDeleteTopology from "@/components/app/topology/modal/ModalDeleteTopology.vue"
import ModalMoveTopology from "@/components/app/topology/modal/ModalMoveTopology.vue"
import ModalEditTopology from "@/components/app/topology/modal/ModalEditTopology.vue"
import ModalCreateFolder from "@/components/app/folder/modal/ModalCreateFolder.vue"
import ModalCreateTopology from "@/components/app/topology/modal/ModalCreateTopology.vue"
import ModalImportTopology from "@/components/app/topology/modal/ModalImportTopology.vue"

export default {
  name: "SidebarLayout",
  components: {
    ModalImportTopology,
    ModalCreateTopology,
    ModalCreateFolder,
    ModalEditTopology,
    ModalMoveTopology,
    ModalDeleteTopology,
    ModalEditFolder,
    ModalDeleteFolder,
    ModalRunTopology,
    Sidebar,
    SidebarToggle,
    Navigation,
    GlobalNotifications,
  },
  watch: {
    $route: {
      immediate: true,
      handler(to) {
        document.title = to.meta.title
      },
    },
  },
}
</script>
