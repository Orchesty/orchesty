<template>
  <LoginLayout>
    <template #title>
      Heslo změněno <v-icon class="icon">mdi-check</v-icon>
    </template>
    <p class="subtitle-2 color-main-text mb-8">
      Nové heslo bylo úspěšně změněno, nyní se můžete přihlásit.
    </p>
    <Button
      class="button"
      color="primary"
      large
      no-text-transform
      :to="{ name: Routes.Login }"
    >
      Přihlásit
    </Button>
  </LoginLayout>
</template>

<script lang="ts">
import { Component, Vue } from "vue-property-decorator";
import Button from "../components/commons/inputsAndControls/Button.vue";
import LoginLayout from "../components/commons/layouts/LoginLayout.vue";
import { Routes } from "../enums";

@Component({
  components: {
    Button,
    LoginLayout,
  },
})
export default class ChangedPasswordPage extends Vue {
  Routes = Routes;
}
</script>

<style lang="scss" scoped>
.icon {
  position: relative;
  top: -3px;
  margin-left: 1rem;
  padding: 4px;
  color: $color-dark-blue;
  font-size: 0.7em;
  border: 2px solid;
  border-radius: 50%;
}

.button {
  font-size: 20px;
  padding: 10px;
  width: 25ch !important;
  min-width: initial !important;
}
</style>
