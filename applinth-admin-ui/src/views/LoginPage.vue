<template>
  <LoginLayout>
    <template #subtitle>
      <div class="subtitle-1">Vítejte zpět</div>
    </template>
    <template #title> Přihlaste se ke svému účtu </template>
    <LoginHandler />
  </LoginLayout>
</template>

<script lang="ts">
import { Component, Vue } from "vue-property-decorator";
import LoginLayout from "../components/commons/layouts/LoginLayout.vue";
import LoginHandler from "../components/auth/LoginHandler.vue";

@Component({
  components: {
    LoginLayout,
    LoginHandler,
  },
})
export default class LoginPage extends Vue {}
</script>

<style lang="scss" scoped></style>
