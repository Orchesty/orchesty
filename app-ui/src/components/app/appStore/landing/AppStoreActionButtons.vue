<template>
  <v-col cols="auto" class="d-flex justify-end align-center ml-auto">
    <v-select
      :value="sdk"
      dense
      outlined
      :label="$t('form.changeSdkValueAppStore')"
      :placeholder="$t('form.changeSdkValueAppStore')"
      :items="sdks"
      item-value="name"
      item-text="name"
      @input="changeSdk"
    />
  </v-col>
</template>

<script>
import { mapActions, mapGetters } from "vuex"
import { LOCAL_STORAGE } from "@/services/enums/localStorageEnums"
import { APP_STORE } from "@/store/modules/appStore/types"

export default {
  name: "AppStoreActionButtons",
  data() {
    return {
      sdks: JSON.parse(localStorage.getItem(LOCAL_STORAGE.IMPLEMENTATIONS))
        .items,
    }
  },
  computed: {
    ...mapGetters(APP_STORE.NAMESPACE, {
      sdk: APP_STORE.GETTERS.GET_SDK,
    }),
  },
  methods: {
    ...mapActions(APP_STORE.NAMESPACE, [APP_STORE.ACTIONS.GET_SDK]),
    async changeSdk(value) {
      await this[APP_STORE.ACTIONS.GET_SDK](value)
    },
  },
  created() {
    if (!this.sdk && this.sdks.length === 1) {
      this.changeSdk(this.sdks[0].name)
    }
  },
}
</script>
