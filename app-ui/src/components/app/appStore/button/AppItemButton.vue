<template>
  <v-btn
    :to="to"
    :disabled="loading ? true : disabled"
    :loading="loading"
    small
    min-width="104"
    :color="color"
    :text="installed"
    :outlined="outlined"
    @click="handleClick"
  >
    <span class="subtitle-2">{{ text }}</span>
  </v-btn>
</template>

<script>
export default {
  name: 'AppItemButton',
  props: {
    disabled: {
      type: Boolean,
      default: false,
    },
    text: {
      type: String,
      default: '',
    },
    to: {
      type: [String, Object],
      default: '',
    },
    loading: {
      type: Boolean,
      default: false,
    },
    installed: {
      type: Boolean,
      default: false,
    },
    color: {
      type: String,
      required: true,
    },
    outlined: {
      type: Boolean,
      default: false,
    },
  },
  methods: {
    handleClick() {
      this.$emit('click')
    },
  },
}
</script>
