<template>
  <v-btn v-if="isSending" :class="customClass" :color="color" type="submit" disabled :text="flat">
    {{ sendingTitle }}
  </v-btn>
  <v-btn v-else :class="customClass" :color="color" :disabled="disabled" :text="flat" @click="onClick">
    {{ buttonTitle }}
  </v-btn>
</template>

<script>
export default {
  name: 'AppButton',
  props: {
    isSending: {
      type: Boolean,
      required: true,
    },
    sendingTitle: {
      type: String,
      required: true,
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
      default: true,
    },
    customClass: {
      type: String,
      required: false,
      default: () => '',
    },
  },
}
</script>
