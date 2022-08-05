<template>
  <v-sheet class="page-wrapper">
    <v-container fluid>
      <v-row dense>
        <v-col style="z-index: 1">
          <span v-if="redirectInTitle" class="text-decoration-underline pointer" @click="redirect">{{ title }}</span>
          <h1 v-else class="headline font-weight-bold">{{ title }}</h1>
        </v-col>
        <v-col class="text-right">
          <slot name="nav-buttons"> </slot>
        </v-col>
      </v-row>
      <v-row dense>
        <v-col cols="12">
          <slot></slot>
        </v-col>
      </v-row>
    </v-container>
  </v-sheet>
</template>

<script>
import { redirectTo } from '@/services/utils/utils'
import { ROUTES } from '@/services/enums/routerEnums'

export default {
  name: 'ContentBasic',
  props: {
    title: {
      type: String,
      required: false,
      default: '',
    },
    center: {
      type: Boolean,
      required: false,
      default: false,
    },
    redirectInTitle: {
      type: Boolean,
      required: false,
      default: false,
    },
  },
  methods: {
    async redirect() {
      await redirectTo(this.$router, { name: ROUTES.APP_STORE.AVAILABLE_APPS })
    },
  },
}
</script>
