<template>
  <AppLayout>
    <div class="wrapper">
      <Heading>{{$t('notFoundPage.header')}}</Heading>
    </div>
  </AppLayout>
</template>

<script lang="ts">
import { Component, Vue } from "vue-property-decorator";
import AppLayout from "../components/commons/layouts/AppLayout.vue";
import Heading from "@/components/Heading.vue";

@Component({
  components: {
    Heading,
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
