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
    <modal-update-node />
    <!--    end-->
  </v-app>
</template>

<script>
import GlobalNotifications from '../../commons/notifications/GlobalNotifications'
import Navigation from '../navigation/Navigation'
import SidebarToggle from '@/components/layout/sidebar/SidebarToggle'
import Sidebar from '@/components/layout/sidebar/Sidebar'
import ModalRunTopology from '@/components/app/topology/modal/ModalRunTopology'
import ModalDeleteFolder from '@/components/app/folder/modal/ModalDeleteFolder'
import ModalEditFolder from '@/components/app/folder/modal/ModalEditFolder'
import ModalDeleteTopology from '@/components/app/topology/modal/ModalDeleteTopology'
import ModalMoveTopology from '@/components/app/topology/modal/ModalMoveTopology'
import ModalEditTopology from '@/components/app/topology/modal/ModalEditTopology'
import ModalCreateFolder from '@/components/app/folder/modal/ModalCreateFolder'
import ModalCreateTopology from '@/components/app/topology/modal/ModalCreateTopology'
import ModalImportTopology from '@/components/app/topology/modal/ModalImportTopology'
import ModalUpdateNode from '@/components/app/topology/modal/ModalUpdateNode'

export default {
  name: 'SidebarLayout',
  components: {
    ModalUpdateNode,
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
