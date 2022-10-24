<template>
  <router-link
    class="d-flex align-center text-decoration-none ml-1"
    :to="{ name: to }"
  >
    <v-icon :color="color">
      {{ icon }}
    </v-icon>
    <span :class="`${color}--text ml-1`">
      {{ $t(text) }}
    </span>
  </router-link>
</template>

<script lang="ts">
import { Vue, Prop, Component } from "vue-property-decorator"

@Component
export default class NavigationItem extends Vue {
  @Prop({ type: String, required: true })
  to!: string

  @Prop({ type: String, required: true })
  icon!: string

  @Prop({ type: String, required: true })
  text!: string

  @Prop({ type: String, default: "white" })
  color!: string
}
</script>
