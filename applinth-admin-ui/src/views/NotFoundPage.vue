<template>
  <AppLayout>
    <div class="wrapper">
      <h1>Stránka na této adrese neexistuje!</h1>
    </div>
  </AppLayout>
</template>

<script lang="ts">
import { Component, Vue } from "vue-property-decorator";
import AppLayout from "../components/commons/layouts/AppLayout.vue";

@Component({
  components: {
    AppLayout,
  },
})
export default class NotFoundPage extends Vue {}
</script>

<style lang="scss" scoped>
.wrapper {
  display: grid;
  place-items: center;
  width: 100%;
  min-height: 20vh;
}
</style>
