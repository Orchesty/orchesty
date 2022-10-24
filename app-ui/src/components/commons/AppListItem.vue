<template>
  <v-list-item v-if="conditional" @click="onClick">
    <v-list-item-title class="d-flex justify-space-between align-center">
      <span class="mr-2" :class="spanClass">{{ $t(text) }}</span>
      <app-icon :color="iconColor" dense> {{ icon }} </app-icon>
    </v-list-item-title>
  </v-list-item>
</template>

<script>
import AppIcon from "@/components/commons/icon/AppIcon"
export default {
  name: "AppListItem",
  components: { AppIcon },
  props: {
    conditional: {
      type: Boolean,
      default: true,
    },
    spanClass: {
      type: String,
      required: true,
    },
    text: {
      type: String,
      required: true,
    },
    iconColor: {
      type: String,
      required: true,
    },
    icon: {
      type: String,
      required: true,
    },
    onClick: {
      type: Function,
      required: true,
    },
  },
}
</script>

<style scoped></style>
