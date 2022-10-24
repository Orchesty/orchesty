<template>
  <basic-card :title="$t('profile.setting.title')">
    <v-list>
      <v-list-item>
        <v-list-item-content>
          {{ $t("page.status.darkMode") }}
        </v-list-item-content>
        <v-list-item-action>
          <v-switch v-model="darkMode" />
        </v-list-item-action>
      </v-list-item>
      <v-list-item>
        <v-list-item-content>
          {{ $t("page.status.language") }}
        </v-list-item-content>
        <v-list-item-action>
          <v-select v-model="language" :items="languages">
            <template #item="{ item }">
              <vue-country-flag-icon :iso="item.flag" class="mr-1" />
              {{ item.text }}
            </template>
            <template #selection="{ item }">
              <vue-country-flag-icon :iso="item.flag" class="mr-1" />
              {{ item.text }}
            </template>
          </v-select>
        </v-list-item-action>
      </v-list-item>
    </v-list>
  </basic-card>
</template>

<script>
import BasicCard from "../../commons/card/BasicCard"
import { ADMIN_USERS } from "@/store/modules/adminUsers/types"
import { mapActions, mapState } from "vuex"
import { AUTH } from "@/store/modules/auth/types"
import { LOCAL_STORAGE } from "@/services/enums/localStorageEnums"

export default {
  name: "SettingHandler",
  components: {
    BasicCard,
  },
  computed: {
    ...mapState(AUTH.NAMESPACE, ["user"]),
  },
  methods: {
    ...mapActions(ADMIN_USERS.NAMESPACE, [
      ADMIN_USERS.ACTIONS.UPDATE_USER_REQUEST,
    ]),
    getSettings() {
      return JSON.parse(localStorage.getItem(LOCAL_STORAGE.USER_SETTINGS))
    },
    initSettings() {
      return {
        darkMode: this.getSettings().darkMode,
        language: this.getSettings().language,
        show: this.getSettings().show,
        languages: [
          { text: "CZ", flag: "cz", value: "cs" },
          { text: "EN", flag: "us", value: "en" },
        ],
      }
    },
  },
  data() {
    return {
      ...this.initSettings(),
    }
  },
  watch: {
    darkMode(value) {
      this.$vuetify.theme.dark = value
      localStorage.setItem(
        LOCAL_STORAGE.USER_SETTINGS,
        JSON.stringify({
          darkMode: value,
          language: this.$i18n.locale,
          show: this.show,
        })
      )
      this[ADMIN_USERS.ACTIONS.UPDATE_USER_REQUEST]({
        data: {
          settings: {
            darkMode: value,
            language: this.$i18n.locale,
            show: this.show,
          },
        },
        id: this.user.id,
      })
    },
    language(value) {
      this.$i18n.locale = value
      localStorage.setItem(
        LOCAL_STORAGE.USER_SETTINGS,
        JSON.stringify({
          darkMode: this.$vuetify.theme.dark,
          language: value,
          show: this.show,
        })
      )
      this[ADMIN_USERS.ACTIONS.UPDATE_USER_REQUEST]({
        data: {
          settings: {
            darkMode: this.$vuetify.theme.dark,
            language: value,
            show: this.show,
          },
        },
        id: this.user.id,
      })
    },
  },
}
</script>
