<template>
  <v-container fluid fill-height class="pa-0">
    <v-row class="sidebar-height-100">
      <v-col
        class="pa-0 side-page-column"
        :class="{ 'ma-0': !show }"
        :lg="toggleColumns(true, false)"
        :cols="toggleColumns(true, true)"
      >
        <v-btn small color="secondary" class="sidebar-button" :style="{ left: offsetLeft }" @click="toggleSideMenu">
          <v-icon v-if="show">mdi-chevron-left</v-icon>
          <v-icon v-else>mdi-chevron-right</v-icon>
        </v-btn>

        <v-sheet v-if="show" class="sidebar-height-100">
          <slot name="sidebar" />
        </v-sheet>
        <run-topology-modal />
        <modal-delete-folder />
        <modal-edit-folder />
        <modal-delete-topology />
        <modal-move-topology />
        <modal-edit-topology />
      </v-col>
      <v-col class="py-0" :lg="toggleColumns(false, false)" :cols="toggleColumns(false, true)">
        <slot />
      </v-col>
    </v-row>
  </v-container>
</template>

<script>
import { ADMIN_USERS } from '../../store/modules/adminUsers/types'
import { mapActions, mapState } from 'vuex'
import { AUTH } from '../../store/modules/auth/types'
import { LOCAL_STORAGE } from '@/enums'
import RunTopologyModal from '@/components/app/topology/modal/ModalRunTopology'
import ModalDeleteFolder from '@/components/app/folder/modal/ModalDeleteFolder'
import ModalEditFolder from '@/components/app/folder/modal/ModalEditFolder'
import ModalDeleteTopology from '@/components/app/topology/modal/ModalDeleteTopology'
import ModalMoveTopology from '@/components/app/topology/modal/ModalMoveTopology'
import ModalEditTopology from '@/components/app/topology/modal/ModalEditTopology'

export default {
  name: 'SidePageLayout',
  components: {
    ModalEditTopology,
    ModalMoveTopology,
    ModalDeleteTopology,
    ModalEditFolder,
    ModalDeleteFolder,
    RunTopologyModal,
  },
  props: {
    title: {
      type: String,
      required: false,
      default: '',
    },
  },
  computed: {
    ...mapState(AUTH.NAMESPACE, ['user']),
    offsetLeft() {
      return this.show ? this.sidePageWidth + 33 + 'px' : '43px'
    },
  },
  data() {
    return {
      show: JSON.parse(localStorage.getItem(LOCAL_STORAGE.USER_SETTINGS)).show || false,
      darkMode: JSON.parse(localStorage.getItem(LOCAL_STORAGE.USER_SETTINGS)).darkMode,
      sidePageWidth: 0,
    }
  },
  methods: {
    ...mapActions(ADMIN_USERS.NAMESPACE, [ADMIN_USERS.ACTIONS.UPDATE_USER_REQUEST]),
    toggleColumns(isLeft) {
      if (this.show) {
        if (this.$vuetify.breakpoint.mdAndDown) {
          return isLeft ? 4 : 8
        } else {
          return isLeft ? 2 : 10
        }
      } else {
        return isLeft ? 0 : 12
      }
    },
    toggleSideMenu() {
      this.show = !this.show
      localStorage.setItem(
        LOCAL_STORAGE.USER_SETTINGS,
        JSON.stringify({ darkMode: this.darkMode, language: this.$i18n.locale, show: this.show })
      )
    },
  },
  watch: {
    async show() {
      await this[ADMIN_USERS.ACTIONS.UPDATE_USER_REQUEST]({
        data: {
          settings: {
            show: this.show,
            darkMode: JSON.parse(localStorage.getItem(LOCAL_STORAGE.USER_SETTINGS)).darkMode,
            language: JSON.parse(localStorage.getItem(LOCAL_STORAGE.USER_SETTINGS)).language,
          },
        },
        id: this.user.user.id,
      })
    },
    '$vuetify.breakpoint.width'() {
      this.sidePageWidth = document.querySelector('.side-page-column').offsetWidth
    },
  },
  updated() {
    this.sidePageWidth = document.querySelector('.side-page-column').offsetWidth
  },
  mounted() {
    this.sidePageWidth = document.querySelector('.side-page-column').offsetWidth
  },
}
</script>

<style lang="scss" scoped>
.sidebar-height-100 {
  height: 100%;
}
.sidebar-button {
  position: fixed;
  top: 65px;
  z-index: 10;
  width: 25px !important;
  height: 25px !important;
  min-width: 25px !important;
}
.sidebar-button-closed {
  left: 43px;
}
.sidebar-button-opened {
  left: 342px;
}
.sidebar-button-opened-mobile {
  left: 363px;
}
</style>
