<template>
  <div>
    <v-card outlined v-for="app of apps" :key="app.id" class="mb-2 pa-5">
      <div class="wrapper">
        <div>
          <v-img
            lazy-src="https://picsum.photos/id/11/10/6"
            height="150"
            width="150"
            src="https://picsum.photos/id/11/500/300"
          />
        </div>
        <div class="d-flex align-center justify-start">
          <h1>{{ app.name }}</h1>
        </div>
        <div class="d-flex flex-column align-center justify-center">
          <h1 class="font-weight-light">Users</h1>
          <span class="display-1 font-weight-bold">{{ app.users }}</span>
        </div>
        <div class="d-flex align-center justify-center">
          <router-link :to="{ name: null }">
            <h1 class="font-weight-light">Detail</h1>
          </router-link>
        </div>
      </div>
    </v-card>
  </div>
</template>

<script lang="ts">
import { Component, Vue } from "vue-property-decorator";

@Component({
  components: {},
})
export default class AppsOverview extends Vue {
  apps = [
    { name: "Amazon Web Services Limited", users: 98, id: 1 },
    { name: "Second App", users: 23, id: 2 },
    { name: "Third App", users: 21, id: 3 },
  ];
}
</script>

<style lang="scss" scoped>
.wrapper {
  display: grid;
  grid-template-columns: 150px minmax(auto, 350px) 1fr 60px;
  gap: 0 16px;
}
</style>
