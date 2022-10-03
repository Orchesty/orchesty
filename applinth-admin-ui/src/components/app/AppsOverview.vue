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
                :src="
                  app.logo
                    ? app.logo
                    : require('@/assets/svg/app-item-placeholder.svg')
                "
              />
            </v-col>
            <v-col class="d-flex justify-center align-center">
              <SubHeading>{{
                app.publicName ? app.publicName : app.appName
              }}</SubHeading>
            </v-col>
            <v-col class="d-flex flex-column justify-center align-center">
              <SubHeading
                >{{ $t("overviewPage.apps.users") }}:
                {{ app.endUsers }}</SubHeading
              >
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
import { Component, Vue, Watch } from "vue-property-decorator";
import { Routes } from "@/enums/Routes";
import BaseProgressBarLinear from "@/components/commons/BaseProgressBarLinear.vue";
import SubHeading from "@/components/commons/typography/SubHeading.vue";
import { IndexedApplicationDetail } from "@/types";
import { Getter } from "vuex-class";
import {
  ApplicationsGetters,
  applicationsNamespace,
} from "@/store/modules/applications";

type UsageStatsAppsRowsInnerRich = UsageStatsAppsRowsInner & {
  logo?: string | null;
  publicName?: string | null;
};

@Component({
  components: { SubHeading, BaseProgressBarLinear },
})
export default class AppsOverview extends Vue {
  @Getter(
    `${applicationsNamespace}/${ApplicationsGetters.IsFetchingApplicationsMetadata}`
  )
  fetchingMetadata!: boolean;

  @Getter(
    `${applicationsNamespace}/${ApplicationsGetters.GetApplicationsMetadata}`
  )
  applicationsMetadata!: IndexedApplicationDetail;

  apps!: UsageStatsAppsRowsInnerRich[];
  isLoading = false;

  Routes = Routes;

  async created() {
    this.isLoading = true;
    this.apps = await callApi<UsageStatsAppsRequest>(api.overview.apps, {
      timeRangeStart: new Date(0).toISOString(),
      timeRangeEnd: new Date().toISOString(),
    });

    this.addMetadataToApplications();

    this.isLoading = false;
  }

  private addMetadataToApplications() {
    for (const app of this.apps) {
      const metadata = this.applicationsMetadata[app.appId as string];
      if (metadata) {
        app.publicName = metadata.publicName;
        app.logo = metadata.logo;
      }
    }
  }

  @Watch("fetchingMetadata")
  private rerenderList() {
    if (!this.fetchingMetadata) {
      this.isLoading = true;
      this.addMetadataToApplications();
      this.isLoading = false;
    }
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
