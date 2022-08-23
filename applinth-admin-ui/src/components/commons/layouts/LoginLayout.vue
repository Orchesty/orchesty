<template>
  <main class="wrapper">
    <div class="background"></div>
    <div class="form">
      <div class="inner">
        <Logo class="mb-16" />
        <slot name="subtitle" />
        <h1 class="heading">
          <slot name="title" />
        </h1>
        <slot />
      </div>
    </div>
  </main>
</template>

<script lang="ts">
import { Component, Vue } from "vue-property-decorator";
import Logo from "@/components/commons/Logo.vue";

@Component({
  components: {
    Logo,
  },
})
export default class LoginLayout extends Vue {}
</script>

<style lang="scss" scoped>
.wrapper {
  height: 100%;
  background-color: $color-white;
}

.form {
  display: flex;
  justify-content: center;
  padding: 2rem;
  padding-top: 20vh;
}

.inner {
  max-width: 50ch;
}

.heading {
  font-size: 30px;
  font-weight: 700;
  padding-bottom: 1rem;
}
</style>
