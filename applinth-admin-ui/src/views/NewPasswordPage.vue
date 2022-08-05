<template>
  <LoginLayout>
    <template #title>Nové heslo</template>
    <NewPassword />
  </LoginLayout>
</template>

<script lang="ts">
import { Component, Vue } from "vue-property-decorator";
import LoginLayout from "../components/commons/layouts/LoginLayout.vue";
import NewPassword from "../components/auth/NewPassword.vue";

@Component({
  components: {
    LoginLayout,
    NewPassword,
  },
})
export default class NewPasswordPage extends Vue {}
</script>

<style lang="scss" scoped></style>
