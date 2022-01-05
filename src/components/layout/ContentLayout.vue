<template>
  <v-container class="pa-5">
    <v-row v-if="isSending">
      <v-col class="text-center">
        <progress-bar-circular />
      </v-col>
    </v-row>
    <v-row v-else dense>
      <slot />
    </v-row>
  </v-container>
</template>

<script>
import ProgressBarCircular from '../commons/progressIndicators/ProgressBarCircular'

export default {
  name: 'ContentLayout',
  components: { ProgressBarCircular },
  props: {
    isSending: {
      type: Boolean,
      required: false,
      default: false,
    },
  },
}
</script>
