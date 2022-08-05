<template>
  <v-chip
    :class="{ active: active }"
    :text-color="active ? 'white' : ''"
    color="primary"
    :outlined="!active"
    link
    @click="$emit('click')"
  >
    <slot />
  </v-chip>
</template>

<script lang="ts">
import { Component, Prop, Vue } from "vue-property-decorator";

@Component
export default class ChipButton extends Vue {
  @Prop({ required: false, type: Boolean, default: false })
  private active!: boolean;
}
</script>

<style lang="scss" scoped></style>
