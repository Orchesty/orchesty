<template>
  <v-sheet class="page-wrapper">
    <v-container class="pb-0" fluid>
      <v-row dense>
        <v-col>
          <v-container fluid>
            <v-row dense>
              <v-col>
                <h3>{{ title }}</h3>
              </v-col>
            </v-row>
          </v-container>
        </v-col>
        <v-col class="text-right">
          <slot name="nav-buttons"> </slot>
        </v-col>
      </v-row>
    </v-container>
    <v-container class="pt-0" fluid>
      <v-row dense>
        <v-col cols="12">
          <v-container class="pt-0" fluid>
            <slot></slot>
          </v-container>
        </v-col>
      </v-row>
    </v-container>
  </v-sheet>
</template>

<script>
export default {
  name: 'PageLayout',
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
<style>
.page-wrapper {
  margin: 1em 1em 1em 1.5em;
  border-radius: 0.75em;
  height: 100%;
}
</style>
