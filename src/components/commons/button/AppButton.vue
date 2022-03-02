<template>
  <v-btn
    v-if="isSending"
    :[type]="type"
    :height="height"
    :class="customClass"
    :color="color"
    :style="customStyle"
    disabled
    :outlined="outlined"
    :text="flat"
    :min-width="minWidth"
  >
    {{ sendingTitle }}
  </v-btn>
  <v-btn
    v-else
    :[type]="type"
    :height="height"
    :class="customClass"
    :color="color"
    :disabled="disabled"
    :text="flat"
    :style="customStyle"
    :min-width="minWidth"
    :outlined="outlined"
    @click="onClick"
  >
    {{ buttonTitle }}
  </v-btn>
</template>

<script>
export default {
  name: 'AppButton',
  props: {
    isSending: {
      type: Boolean,
      required: false,
    },
    sendingTitle: {
      type: String,
      required: false,
      default: () => '',
    },
    type: {
      type: String,
      required: false,
      default: () => '',
    },
    outlined: Boolean,
    buttonTitle: {
      type: String,
      required: true,
    },
    onClick: {
      type: Function,
      required: true,
    },
    disabled: Boolean,
    color: {
      type: String,
      required: false,
      default: 'primary',
    },
    flat: {
      type: Boolean,
      default: false,
    },
    minWidth: {
      type: Number,
      default: 110,
    },
    customClass: {
      type: String,
      required: false,
      default: () => '',
    },
    customStyle: {
      type: Object,
      required: false,
      default: () => {},
    },
    height: {
      type: Number,
      default: 40,
    },
  },
}
</script>
