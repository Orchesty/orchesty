<template>
  <v-sheet class="page-wrapper">
    <v-container fluid>
      <v-row>
        <v-col cols="12">
          <page-tabs-header :title="title" :is-topology="isTopology" :tabs="tabs" />
        </v-col>
      </v-row>

      <v-row>
        <v-col cols="12" class="pt-0">
          <v-container fluid class="pt-0">
            <router-view :key="$route.path" />
          </v-container>
        </v-col>
      </v-row>
    </v-container>
  </v-sheet>
</template>

<script>
import PageTabsHeader from '@/components/layout/PageTabsHeader'

export default {
  name: 'PageTabsLayout',
  components: { PageTabsHeader },
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
<style lang="scss" scoped>
.page-wrapper {
  margin: 1em 1em 1em 1.5em;
  border-radius: 0.75em;
  height: 100%;
}
</style>
