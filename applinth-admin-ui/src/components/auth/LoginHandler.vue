<template>
  <LoginForm :on-submit="login" />
</template>

<script lang="ts">
import { Component, Vue } from "vue-property-decorator";
import { Action } from "vuex-class";
import { AuthActions, authNamespace } from "../../store/modules/auth";
import LoginForm from "./LoginForm.vue";
import { TLoginForm } from "./types";

@Component({
  components: {
    LoginForm,
  },
})
export default class LoginHandler extends Vue {
  @Action(`${authNamespace}/${AuthActions.Login}`)
  private login!: (payload: TLoginForm) => Promise<boolean>;
}
</script>

<style lang="scss" scoped></style>
