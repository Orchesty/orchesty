<template>
  <v-btn
    :fab="fab"
    :icon="icon"
    :[type]="type"
    :height="icon ? '36' : height"
    :class="customClass"
    :color="color"
    :disabled="isSending ? true : disabled"
    :text="flat"
    :width="width"
    :style="customStyle"
    :min-width="icon ? '36' : minWidth"
    :outlined="outlined"
    :loading="loading"
    :to="!!to ? to : null"
    v-bind="attrs"
    v-on="on"
    @click="onClick"
  >
    <template v-if="icon || fab">
      <slot name="icon"></slot>
    </template>

    <template v-else>
      {{ isSending ? sendingTitle : buttonTitle }}
    </template>
  </v-btn>
</template>

<script>
export default {
  name: 'AppButton',
  props: {
    icon: {
      type: Boolean,
      default: false,
    },
    fab: {
      type: Boolean,
      default: false,
    },
    on: {
      type: Object,
      default: () => {},
    },
    attrs: {
      type: Object,
      default: () => {},
    },
    isSending: {
      type: Boolean,
      required: false,
    },
    sendingTitle: {
      type: String,
      required: false,
      default: () => '',
    },
    loading: {
      type: Boolean,
      default: false,
    },
    width: {
      type: String,
      default: 'auto',
    },
    type: {
      type: String,
      required: false,
      default: () => '',
    },
    outlined: Boolean,
    buttonTitle: {
      type: String,
      default: '',
    },
    to: {
      type: Object,
      default: () => {},
    },
    onClick: {
      type: Function,
      default: () => {},
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
