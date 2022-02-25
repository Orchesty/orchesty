<template>
  <v-btn
    v-if="isSending"
    :height="height"
    :class="customClass"
    :color="color"
    type="submit"
    :style="customStyle"
    disabled
    :text="flat"
    min-width="110"
  >
    {{ sendingTitle }}
  </v-btn>
  <v-btn
    v-else
    :height="height"
    :class="customClass"
    :color="color"
    :disabled="disabled"
    :text="flat"
    :style="customStyle"
    min-width="110"
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
