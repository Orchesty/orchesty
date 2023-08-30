<template>
  <v-card flat>
    <v-toolbar v-if="!noToolbar" flat dense>
      <v-toolbar-title v-if="title" class="body-1">{{ title }}</v-toolbar-title>

      <span v-if="tooltip" class="ml-3">
        <tooltip :text="tooltip" />
      </span>

      <slot name="toolbar-left"></slot>

      <v-spacer />

      <slot name="toolbar"></slot>
    </v-toolbar>

    <v-divider v-if="!flat && !noToolbar" />

    <template v-if="isSending">
      <v-card-text align="center">
        <progress-bar-circular />
      </v-card-text>
    </template>

    <template v-else>
      <info-text v-if="infoTitle">{{ infoTitle }}</info-text>

      <v-card-text v-if="!noCardText">
        <slot></slot>
      </v-card-text>

      <slot v-else></slot>
    </template>
  </v-card>
</template>

<script>
import ProgressBarCircular from '../progressIndicators/ProgressBarCircular'
import InfoText from './InfoText'
import Tooltip from '../tooltip/Tooltip'

export default {
  name: 'BasicCard',
  components: { InfoText, ProgressBarCircular, Tooltip },
  props: {
    title: {
      type: String,
      default: '',
    },
    isSending: {
      type: Boolean,
      default: false,
    },
    noCardText: {
      type: Boolean,
      default: false,
    },
    noToolbar: {
      type: Boolean,
      default: false,
    },
    infoTitle: {
      type: String,
      default: '',
    },
    flat: {
      type: Boolean,
      default: false,
    },
    tooltip: {
      type: String,
      default: '',
    },
  },
}
</script>
