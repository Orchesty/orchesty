<template>
  <AppLayout>
    <Heading>{{ $t("overviewPage.header") }}</Heading>
    <StatusCardList class="mt-5" />
    <AppsOverview class="mt-6" />
  </AppLayout>
</template>

<script lang="ts">
import { Component, Vue } from "vue-property-decorator"
import AppLayout from "../components/commons/layouts/AppLayout.vue"
import StatusCardList from "@/components/status-cards/StatusCardList.vue"
import AppsOverview from "@/components/app/AppsOverview.vue"
import Heading from "@/components/commons/typography/Heading.vue"

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
