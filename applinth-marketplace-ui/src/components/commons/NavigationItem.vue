<template>
  <router-link
    class="d-flex align-center text-decoration-none"
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

<script>
export default {
  name: "NavigationItem",
  props: {
    to: {
      type: String,
      required: true,
    },
    icon: {
      type: String,
      required: true,
    },
    text: {
      type: String,
      required: true,
    },
    color: {
      type: String,
      default: "white",
    },
  },
}
</script>
