<template>
  <v-card flat>
    <v-card-text v-if="isSending" class="pa-0">
      <progress-bar-linear class="ma-0" />
    </v-card-text>
    <v-card-text v-else>
      <slot />
    </v-card-text>
  </v-card>
</template>

<script>
import ProgressBarLinear from '@/components/commons/progressIndicators/ProgressBarLinear'
export default {
  name: 'ExpandedRow',
  components: { ProgressBarLinear },
  props: {
    isSending: {
      type: Boolean,
      default: false,
    },
  },
}
</script>
