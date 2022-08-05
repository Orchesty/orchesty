<template>
  <AppLayout>
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

@Component({
  components: {
    AppLayout,
    StatusCardList,
    AppsOverview,
  },
})
export default class DashboardPage extends Vue {}
</script>
