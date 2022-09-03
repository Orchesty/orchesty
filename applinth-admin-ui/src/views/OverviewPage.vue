<template>
  <AppLayout>
    <v-row>
      <v-col>
        <Heading>{{ $t("overviewPage.header") }}</Heading>
      </v-col>
    </v-row>

    <v-row>
      <v-col>
        <StatusCardList />
      </v-col>
    </v-row>
    <v-row>
      <v-col>
        <AppsOverview />
      </v-col>
    </v-row>
  </AppLayout>
</template>

<script lang="ts">
import { Component, Vue } from "vue-property-decorator";
import AppLayout from "../components/commons/layouts/AppLayout.vue";
import StatusCardList from "@/components/app/StatusCardList.vue";
import AppsOverview from "@/components/app/AppsOverview.vue";
import Heading from "@/components/Heading.vue";

@Component({
  components: {
    Heading,
    AppLayout,
    StatusCardList,
    AppsOverview,
  },
})
export default class DashboardPage extends Vue {}
</script>
