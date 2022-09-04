<template>
  <div>
    <div v-if="isLoading">
      <BaseProgressBarLinear />
    </div>
    <div v-else-if="!isLoading && apps.length">
      <v-card outlined v-for="app of apps" :key="app.id" class="mb-2 pa-5">
        <v-container>
          <v-row>
            <v-col cols="auto" class="d-flex">
              <v-img
                class="ma-auto"
                max-height="70"
                max-width="70"
                contain
                src="https://img.icons8.com/windows/512/ios-application-placeholder.png"
              />
            </v-col>
            <v-col class="d-flex justify-center align-center">
              <SubHeading>{{ app.appName }}</SubHeading>
            </v-col>
            <v-col class="d-flex flex-column justify-center align-center">
              <SubHeading>{{$t('overviewPage.apps.users')}}: {{ app.endUsers }}</SubHeading>
            </v-col>
            <v-col class="d-flex flex-column justify-center align-end">
              <router-link
                class="link"
                :to="{
                  name: Routes.ApplicationDetail,
                  params: { id: app.appName },
                }"
              >
                <span>Detail</span>
              </router-link>
            </v-col>
          </v-row>
        </v-container>
      </v-card>
    </div>
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
import BaseProgressBarLinear from "@/components/commons/BaseProgressBarLinear.vue";
import SubHeading from "@/components/commons/typography/SubHeading.vue";

@Component({
  components: { SubHeading, BaseProgressBarLinear },
})
export default class AppsOverview extends Vue {
  @Getter(`${authNamespace}/${AuthGetters.GetUser}`)
  currentUser!: User;

  apps: UsageStatsAppsRowsInner = [] as UsageStatsAppsRowsInner;
  isLoading = false;

  Routes = Routes;

  async created() {
    this.isLoading = true;
    this.apps = await callApi<UsageStatsAppsRequest>(api.overview.apps, {
      timeRangeStart: new Date(0).toISOString(),
      timeRangeEnd: new Date().toISOString(),
      tenantId: this.currentUser.tenantId ?? undefined,
    });
    this.isLoading = false;
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
