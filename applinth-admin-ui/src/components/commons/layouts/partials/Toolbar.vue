<template>
  <div style="display: flex; align-items: center">
    <div class="mr-3">{{ auth }} {{ auth }}</div>
    <v-menu offset-y>
      <template>
        <v-avatar>
          <img src="@/assets/img/user_avatar.svg" alt="Avatar" />
        </v-avatar>
      </template>
      <ToolbarItems />
    </v-menu>
  </div>
</template>

<script lang="ts">
import { Component, Vue } from "vue-property-decorator";
import { State } from "vuex-class";
import { AuthState } from "../../../../store/modules/auth";
import { authNamespace } from "../../../../store/modules/auth/types";
import { userSettings } from "../../../../utils/userSettings";
import ToolbarItems from "./ToolbarItems.vue";

@Component({
  components: {
    ToolbarItems,
  },
})
export default class Toolbar extends Vue {
  @State(authNamespace)
  private auth!: AuthState;
  userSettings = userSettings;
}
</script>
