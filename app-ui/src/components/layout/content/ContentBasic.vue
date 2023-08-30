<template>
  <v-sheet class="page-wrapper">
    <v-container fluid>
      <v-row dense>
        <v-col style="z-index: 1">
          <h1 class="headline font-weight-bold">{{ title }}</h1>
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
export default {
  name: 'ContentBasic',
  computed: {
    isCenter() {
      return !this.center
    },
  },
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
  },
}
</script>
