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
          <h1>{{ app.appName }}</h1>
        </div>
        <div class="d-flex flex-column align-center justify-center">
          <h1 class="font-weight-light">Users</h1>
          <span class="display-1 font-weight-bold">{{ app.endUsers }}</span>
        </div>
        <div class="d-flex align-center justify-center">
          <router-link
            :to="{
              name: Routes.ApplicationDetail,
              params: { id: app.appName },
            }"
          >
            <h1 class="font-weight-light">Detail</h1>
          </router-link>
        </div>
      </div>
    </v-card>
  </div>
</template>

<script lang="ts">
import { api } from "@/api";
import {
  UsageStatsAppsRequest,
  UsageStatsAppsRowsInner,
} from "@/api/generated";
import { callApi } from "@/utils/apiClient";
import { Component, Vue } from "vue-property-decorator";
import { authNamespace, AuthGetters, User } from "@/store/modules/auth";
import { Getter } from "vuex-class";
import { Routes } from "@/enums/Routes";

@Component({
  components: {},
})
export default class AppsOverview extends Vue {
  @Getter(`${authNamespace}/${AuthGetters.GetUser}`)
  currentUser!: User;

  apps: UsageStatsAppsRowsInner = [] as UsageStatsAppsRowsInner;

  Routes = Routes;

  async created() {
    this.apps = await callApi<UsageStatsAppsRequest>(api.overview.apps, {
      timeRangeStart: new Date(0).toISOString(),
      timeRangeEnd: new Date().toISOString(),
      tenantId: this.currentUser.tenantId ?? undefined,
    });
  }
}
</script>

<style lang="scss" scoped>
.wrapper {
  display: grid;
  grid-template-columns: 150px minmax(auto, 350px) 1fr 60px;
  gap: 0 16px;
}
</style>
