<template>
  <v-list-item v-if="conditional" @click="onClick">
    <v-list-item-title class="d-flex justify-space-between align-center">
      <span class="mr-2" :class="spanClass">{{ $t(text) }}</span>
      <BaseIcon :color="iconColor" dense> {{ icon }} </BaseIcon>
    </v-list-item-title>
  </v-list-item>
</template>

<script>
import BaseIcon from "@/components/commons/icon/BaseIcon"
export default {
  name: "AppListItem",
  components: { BaseIcon },
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
