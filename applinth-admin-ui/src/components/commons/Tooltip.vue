<template>
  <v-tooltip top>
    <template v-slot:activator="{ on, attrs }">
      <v-icon color="primary" v-bind="attrs" v-on="on">mdi-information</v-icon>
    </template>
    <span>{{ text }}</span>
  </v-tooltip>
</template>

<script lang="ts">
import { Component, Prop, Vue } from "vue-property-decorator";

@Component
export default class Tooltip extends Vue {
  @Prop({ required: true, type: String })
  private text!: string;
}
</script>

<style lang="scss" scoped></style>
