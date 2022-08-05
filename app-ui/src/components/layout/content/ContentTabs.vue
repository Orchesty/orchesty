<template>
  <v-sheet class="page-wrapper">
    <v-container fluid>
      <v-row dense>
        <v-col cols="12">
          <content-tabs-header :title="title" :is-topology="isTopology" :tabs="tabs" />
        </v-col>
      </v-row>

      <v-row dense>
        <v-col cols="12" class="pt-0">
          <router-view />
        </v-col>
      </v-row>
    </v-container>
  </v-sheet>
</template>

<script>
import ContentTabsHeader from '@/components/layout/content/ContentTabsHeader'

export default {
  name: 'ContentTabs',
  components: { ContentTabsHeader },
  props: {
    title: {
      type: String,
      required: false,
      default: '',
    },
    tabs: {
      type: Array,
      required: true,
    },
    isTopology: {
      type: Boolean,
      default: false,
    },
  },
}
</script>
